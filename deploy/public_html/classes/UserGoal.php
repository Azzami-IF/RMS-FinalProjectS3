<?php

class UserGoal
{
    private PDO $db;

        public function __construct(PDO $db)
        {
            $this->db = $db;
        }

        // Evaluasi otomatis goal mingguan & update progress
        public function evaluateAndUpdateProgress(int $userId, array $nutritionStats, float $currentWeight = null): void
        {
            $goal = $this->findActive($userId);
            if (!$goal) return;

            // Hitung progress kalori dan nutrisi
            $calorieTarget = (float)($goal['daily_calorie_target'] ?? 0);
            $proteinTarget = (float)($goal['daily_protein_target'] ?? 0);
            $fatTarget = (float)($goal['daily_fat_target'] ?? 0);
            $carbsTarget = (float)($goal['daily_carbs_target'] ?? 0);

            $avgCalories = (float)($nutritionStats['avg_calories'] ?? 0);
            $avgProtein = (float)($nutritionStats['avg_protein'] ?? 0);
            $avgFat = (float)($nutritionStats['avg_fat'] ?? 0);
            $avgCarbs = (float)($nutritionStats['avg_carbs'] ?? 0);

            $scores = [];
            $scoreFromTarget = static function (float $avg, float $target): ?float {
                if ($target <= 0) return null;
                $pct = ($avg / $target) * 100;
                $delta = abs(100 - $pct);
                return max(0.0, 100.0 - min(100.0, $delta));
            };

            $calorieScore = $scoreFromTarget($avgCalories, $calorieTarget);
            if ($calorieScore !== null) $scores[] = $calorieScore;
            $proteinScore = $scoreFromTarget($avgProtein, $proteinTarget);
            if ($proteinScore !== null) $scores[] = $proteinScore;
            $fatScore = $scoreFromTarget($avgFat, $fatTarget);
            if ($fatScore !== null) $scores[] = $fatScore;
            $carbsScore = $scoreFromTarget($avgCarbs, $carbsTarget);
            if ($carbsScore !== null) $scores[] = $carbsScore;

            // Weight progress (optional)
            $weightScore = null;
            if ($currentWeight !== null && ($goal['target_weight_kg'] ?? null) !== null && $goal['target_weight_kg'] !== '') {
                $goalStartDate = null;
                if (!empty($goal['created_at'])) {
                    $goalStartDate = date('Y-m-d', strtotime((string)$goal['created_at']));
                }

                $startWeight = null;
                if ($goalStartDate) {
                    $stmt = $this->db->prepare(
                        "SELECT weight_kg FROM weight_logs WHERE user_id = ? AND logged_at <= ? ORDER BY logged_at DESC LIMIT 1"
                    );
                    $stmt->execute([$userId, $goalStartDate]);
                    $startWeight = $stmt->fetchColumn();

                    if ($startWeight === false || $startWeight === null) {
                        $stmt = $this->db->prepare(
                            "SELECT weight_kg FROM weight_logs WHERE user_id = ? AND logged_at >= ? ORDER BY logged_at ASC LIMIT 1"
                        );
                        $stmt->execute([$userId, $goalStartDate]);
                        $startWeight = $stmt->fetchColumn();
                    }
                }

                if ($startWeight === false || $startWeight === null) {
                    $startWeight = $currentWeight;
                }

                $startWeight = (float)$startWeight;
                $targetWeight = (float)$goal['target_weight_kg'];
                $currWeight = (float)$currentWeight;

                $goalType = (string)($goal['goal_type'] ?? 'maintain');

                // Base progress: how far you've moved toward the target weight.
                $distanceScore = null;
                if ($startWeight !== $targetWeight) {
                    if ($goalType === 'weight_loss') {
                        $den = ($startWeight - $targetWeight);
                        if ($den != 0.0) {
                            $distanceScore = (($startWeight - $currWeight) / $den) * 100.0;
                        }
                    } elseif ($goalType === 'weight_gain' || $goalType === 'muscle_gain') {
                        $den = ($targetWeight - $startWeight);
                        if ($den != 0.0) {
                            $distanceScore = (($currWeight - $startWeight) / $den) * 100.0;
                        }
                    } else {
                        // maintain: score high when close to start
                        $distanceScore = max(0.0, 100.0 - min(100.0, (abs($currWeight - $startWeight) / max(1.0, $startWeight)) * 100.0));
                    }
                } else {
                    $distanceScore = 100.0;
                }

                if ($distanceScore !== null) {
                    $distanceScore = max(0.0, min(100.0, $distanceScore));
                }

                // Schedule awareness: compare current weight with expected weight by now (using target_date or weekly_weight_change).
                $scheduleScores = [];

                $startDt = $goalStartDate ? DateTime::createFromFormat('Y-m-d', (string)$goalStartDate) : null;
                $todayDt = new DateTime('today');

                // (1) Using target_date (linear interpolation from start->target)
                if (!empty($goal['target_date']) && $startDt instanceof DateTime) {
                    $targetDt = DateTime::createFromFormat('Y-m-d', (string)$goal['target_date']);
                    if ($targetDt instanceof DateTime) {
                        $totalDays = (int)$startDt->diff($targetDt)->format('%r%a');
                        $elapsedDays = (int)$startDt->diff($todayDt)->format('%r%a');

                        if ($totalDays !== 0) {
                            $t = $elapsedDays / $totalDays;
                            $t = max(0.0, min(1.0, $t));
                            $expected = $startWeight + (($targetWeight - $startWeight) * $t);

                            $den = abs($targetWeight - $startWeight);
                            if ($den > 0) {
                                $scheduleScore = 100.0 - min(100.0, (abs($currWeight - $expected) / $den) * 100.0);
                                $scheduleScores[] = max(0.0, min(100.0, $scheduleScore));
                            }
                        }
                    }
                }

                // (2) Using weekly_weight_change (expected change by now)
                if (!empty($goal['weekly_weight_change']) && $startDt instanceof DateTime) {
                    $weekly = abs((float)$goal['weekly_weight_change']);
                    if ($weekly > 0) {
                        $elapsedDays = (int)$startDt->diff($todayDt)->format('%r%a');
                        $elapsedWeeks = max(0.0, $elapsedDays / 7.0);
                        $expectedDelta = $weekly * $elapsedWeeks;

                        $expected = $startWeight;
                        if ($goalType === 'weight_loss') {
                            $expected = $startWeight - $expectedDelta;
                        } elseif ($goalType === 'weight_gain' || $goalType === 'muscle_gain') {
                            $expected = $startWeight + $expectedDelta;
                        }

                        $den = abs($targetWeight - $startWeight);
                        if ($den > 0) {
                            $scheduleScore = 100.0 - min(100.0, (abs($currWeight - $expected) / $den) * 100.0);
                            $scheduleScores[] = max(0.0, min(100.0, $scheduleScore));
                        }
                    }
                }

                // Combine weight sub-scores
                $weightParts = [];
                if ($distanceScore !== null) $weightParts[] = $distanceScore;
                foreach ($scheduleScores as $sc) $weightParts[] = $sc;

                if (count($weightParts)) {
                    $weightScore = array_sum($weightParts) / count($weightParts);
                }

                if ($weightScore !== null) {
                    $scores[] = max(0.0, min(100.0, $weightScore));
                }
            }

            $progress = 0;
            if (count($scores)) {
                $progress = (int)round(array_sum($scores) / count($scores));
            }

            // Evaluasi ringkas
            $eval = [];
            if ($progress >= 85) {
                $eval[] = 'Asupan kalori sudah sangat sesuai target.';
            } elseif ($calorieTarget > 0 && $avgCalories > $calorieTarget * 1.1) {
                $eval[] = 'Asupan kalori rata-rata melebihi target. Perlu dikurangi.';
            } elseif ($calorieTarget > 0 && $avgCalories < $calorieTarget * 0.9) {
                $eval[] = 'Asupan kalori rata-rata di bawah target. Perlu ditingkatkan.';
            }
            if ($proteinTarget > 0 && $avgProtein < $proteinTarget * 0.9) {
                $eval[] = 'Asupan protein kurang dari target.';
            }
            if ($fatTarget > 0 && $avgFat > $fatTarget * 1.1) {
                $eval[] = 'Asupan lemak melebihi target.';
            }
            if ($carbsTarget > 0 && $avgCarbs > $carbsTarget * 1.1) {
                $eval[] = 'Asupan karbohidrat melebihi target.';
            }
            if ($currentWeight && $goal['target_weight_kg']) {
                $delta = round($currentWeight - $goal['target_weight_kg'], 1);
                if (abs($delta) < 0.5) {
                    $eval[] = 'Target berat badan hampir tercapai!';
                }
            }
            if ($weightScore !== null && ($goal['target_weight_kg'] ?? null) !== null && $goal['target_weight_kg'] !== '') {
                $eval[] = 'Progres berat badan: ' . (int)round($weightScore) . '%.'.
                    (!empty($goal['weekly_weight_change']) ? ' (mengikuti target mingguan)' : '').
                    (!empty($goal['target_date']) ? ' (mengikuti target tanggal)' : '');
            }
            $evaluation = implode(' ', $eval);

            // Update goal
            $stmt = $this->db->prepare("UPDATE user_goals SET evaluation=?, progress=? WHERE id=?");
            $stmt->execute([$evaluation, $progress, $goal['id']]);
        }

        // Saran target otomatis berbasis data (misal: rekomendasi protein/fat/carb)
        public static function suggestTargets(float $weightKg, string $goalType): array
        {
            // Saran sederhana: protein 1.2-1.6g/kg, fat 0.8g/kg, carb sisanya
            $protein = round($weightKg * ($goalType === 'muscle_gain' ? 1.6 : 1.2));
            $fat = round($weightKg * 0.8);
            $calorie = 2000;
            switch ($goalType) {
                case 'weight_loss':
                    $calorie = 1800;
                    break;
                case 'weight_gain':
                    $calorie = 2500;
                    break;
                case 'muscle_gain':
                    $calorie = 2300;
                    break;
            }
            $carb = max(0, round(($calorie - ($protein * 4 + $fat * 9)) / 4));
            return [
                'daily_calorie_target' => $calorie,
                'daily_protein_target' => $protein,
                'daily_fat_target' => $fat,
                'daily_carbs_target' => $carb
            ];
        }

    /** @return array|false */
    public function findActive(int $userId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_goals
             WHERE user_id = ? AND is_active = TRUE
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        // Set previous goals as inactive
        $stmt = $this->db->prepare(
            "UPDATE user_goals SET is_active = FALSE WHERE user_id = ?"
        );
        $stmt->execute([$data['user_id']]);

        // Tambahan field: evaluasi, status, last_notif, progress
        $stmt = $this->db->prepare(
            "INSERT INTO user_goals
             (user_id, goal_type, target_weight_kg, target_date, weekly_weight_change,
              daily_calorie_target, daily_protein_target, daily_fat_target, daily_carbs_target,
              evaluation, status, last_notif, progress, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', 'active', NULL, 0, TRUE)"
        );
        $stmt->execute([
            $data['user_id'],
            $data['goal_type'],
            $data['target_weight_kg'] ?? null,
            $data['target_date'] ?? null,
            $data['weekly_weight_change'] ?? null,
            $data['daily_calorie_target'],
            $data['daily_protein_target'] ?? null,
            $data['daily_fat_target'] ?? null,
            $data['daily_carbs_target'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_goals SET
             goal_type=?, target_weight_kg=?, target_date=?, weekly_weight_change=?,
             daily_calorie_target=?, daily_protein_target=?, daily_fat_target=?,
             daily_carbs_target=?, evaluation=?, status=?, last_notif=?, progress=?, updated_at=CURRENT_TIMESTAMP
             WHERE id=?"
        );
        $stmt->execute([
            $data['goal_type'],
            $data['target_weight_kg'] ?? null,
            $data['target_date'] ?? null,
            $data['weekly_weight_change'] ?? null,
            $data['daily_calorie_target'],
            $data['daily_protein_target'] ?? null,
            $data['daily_fat_target'] ?? null,
            $data['daily_carbs_target'] ?? null,
            $data['evaluation'] ?? '',
            $data['status'] ?? 'active',
            $data['last_notif'] ?? null,
            $data['progress'] ?? 0,
            $id
        ]);
    }

    public function history(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_goals
             WHERE user_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}