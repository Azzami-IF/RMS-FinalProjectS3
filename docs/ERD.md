# ERD (Entity Relationship Diagram)

Berikut ERD ringkas berdasarkan [query.sql](query.sql).

```mermaid
erDiagram
    users {
        INT id PK
        VARCHAR name
        VARCHAR email
        ENUM role
        INT daily_calorie_goal
        TIMESTAMP created_at
    }

    food_categories {
        INT id PK
        VARCHAR name
    }

    foods {
        INT id PK
        INT category_id FK
        INT created_by FK
        VARCHAR name
        DECIMAL calories
    }

    meal_types {
        INT id PK
        VARCHAR name
        VARCHAR display_name
    }

    schedules {
        INT id PK
        INT user_id FK
        INT food_id FK
        INT meal_type_id FK
        DATE schedule_date
        DECIMAL quantity
        DECIMAL calories_consumed
    }

    notifications {
        INT id PK
        INT user_id FK
        VARCHAR title
        VARCHAR action_url
        ENUM channel
        ENUM status
        TIMESTAMP created_at
    }

    user_goals {
        INT id PK
        INT user_id FK
        ENUM goal_type
        INT daily_calorie_target
        TINYINT is_active
    }

    user_preferences {
        INT id PK
        INT user_id FK
        VARCHAR preference_key
        TEXT preference_value
    }

    weight_logs {
        INT id PK
        INT user_id FK
        DECIMAL weight_kg
        DATE logged_at
    }

    users ||--o{ schedules : logs
    foods ||--o{ schedules : consumed
    meal_types ||--o{ schedules : categorizes

    food_categories ||--o{ foods : groups
    users ||--o{ foods : creates

    users ||--o{ notifications : receives
    users ||--o{ user_goals : sets
    users ||--o{ user_preferences : configures
    users ||--o{ weight_logs : tracks
```

Catatan:
- `notifications.channel` dipakai untuk membedakan `in_app` vs `email` log.
- Beberapa entitas memiliki kolom tambahan (lihat detail lengkap di [query.sql](query.sql)).
