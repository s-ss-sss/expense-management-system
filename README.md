# 旅費請求システム（Expense Management System）

## Overview
社内向けの旅費請求業務を効率化するためのWebアプリケーションです。  
ユーザーの申請から管理者のデータ管理を一元管理できる構成でPHP8を用いて開発しています。

## Demo
**URL**
- https://dolzap.conohawing.com/expense/

**Demo Account**
- USER：demo@example.com
- PASS：demo1234

**Note**
- デモ環境では一部機能（管理機能・メール送信）に制限があります
- データは毎日0時に自動リセットされます

## Features
### User
- ログイン / ログアウト機能
- セッションタイムアウト機能
- 旅費請求機能（10件まで同時申請可能）
- 請求履歴確認機能（一覧・絞り込み検索）
- よく使うコース機能（一覧・登録・編集・削除）

### Admin
- 請求データ管理機能（一覧・CSVダウンロード・絞り込み検索）
- ユーザー管理機能（一覧・登録・編集・削除）
- メール宛先管理機能（一覧・登録・編集・削除・並び替え）
- 路線マスタ管理機能（一覧・登録・編集・削除・並び替え）
- 種別マスタ管理機能（一覧・登録・編集・削除・並び替え）

### Batch
- 実行ログ出力機能
- 多重起動防止機能
- デモデータリセット処理（毎日0時に実行）
- デモデータ再投入処理（毎日0時に実行）

## Stack
### Frontend
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=black)
![jQuery](https://img.shields.io/badge/jQuery-0769AD?logo=jquery&logoColor=white)

### Backend
![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![Smarty](https://img.shields.io/badge/Smarty-orange)
![ADODB](https://img.shields.io/badge/ADODB-blue)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)

### Infrastructure
![Apache](https://img.shields.io/badge/Apache-D22128?logo=apache&logoColor=white)
![cron](https://img.shields.io/badge/cron-grey)

### Others
![Git](https://img.shields.io/badge/Git-F05032?logo=git&logoColor=white)
![GitHub](https://img.shields.io/badge/GitHub-181717?logo=github&logoColor=white)

## Directory
```bash
expense/
├── htdocs/
│   ├── index.php             # ルーティング（ユーザー）
│   ├── admin/                # ルーティング（管理）
│   ├── js/                   # JavaScript
│   ├── css/                  # CSS
│   └── img/                  # 画像
├── lib/
│   ├── conf/
│   │   └── Common.conf.php   # 定数設定
│   ├── sys/
│   │   ├── controllers/      # コントローラ
│   │   ├── modules/          # ビジネスロジック / DAO
│   │   └── batch/            # バッチ処理
│   └── templates/
│       ├── user/             # Smartyテンプレート（ユーザー）
│       └── admin/            # Smartyテンプレート（管理）
├── packages/
│   ├── smarty/               # Smarty（外部）
│   └── adodb5/               # ADODB（外部）
├── applogs/                  # ログ出力
└── .env                      # 環境変数
```

## Database
### t_users（ユーザーデータ）
| Column      | Type         | Null | Key | Default              | Description |
|-------------|-------------|------|-----|----------------------|------------|
| id          | INT(11)     | NO   | PK  | AUTO_INCREMENT       | ユーザーID |
| name        | VARCHAR(40) | NO   | -   | -                    | 氏名 |
| email       | VARCHAR(255)| NO   | UQ  | -                    | メールアドレス |
| password    | VARCHAR(255)| NO   | -   | -                    | パスワード（ハッシュ化） |
| is_admin    | TINYINT(1)  | NO   | -   | 0                    | 管理者フラグ |
| is_active   | TINYINT(1)  | NO   | -   | 1                    | 有効フラグ |
| created_at  | TIMESTAMP   | NO   | -   | CURRENT_TIMESTAMP    | 登録日時 |
| updated_at  | TIMESTAMP   | NO   | -   | CURRENT_TIMESTAMP    | 更新日時 |

### t_expenses（請求データ）
| Column        | Type          | Null | Key | Default           | Description |
|--------------|--------------|------|-----|------------------|------------|
| id           | INT(11)      | NO   | PK  | AUTO_INCREMENT   | 申請ID |
| user_id      | INT(11)      | NO   | -   | -                | ユーザーID |
| purchase_date| DATE         | NO   | -   | -                | 購入日 |
| route_id     | INT(11)      | NO   | -   | -                | 路線ID |
| type_id      | INT(11)      | NO   | -   | -                | 種別ID |
| section_from | VARCHAR(40)  | NO   | -   | -                | 区間（始） |
| section_to   | VARCHAR(40)  | NO   | -   | -                | 区間（終） |
| fee          | INT(11)      | NO   | -   | -                | 料金 |
| note         | VARCHAR(100) | YES  | -   | NULL             | 訪問先 |
| cancel_reason| VARCHAR(255) | YES  | -   | NULL             | 取消理由 |
| is_active    | TINYINT(1)   | NO   | -   | 1                | 有効フラグ（論理削除） |
| created_at   | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 登録日時 |
| updated_at   | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 更新日時 |

### t_courses（よく使うコースデータ）
| Column        | Type          | Null | Key | Default           | Description |
|--------------|--------------|------|-----|------------------|------------|
| id           | INT(11)      | NO   | PK  | AUTO_INCREMENT   | コースID |
| user_id      | INT(11)      | NO   | -   | -                | ユーザーID |
| course_name  | VARCHAR(40)  | NO   | -   | -                | コース名 |
| route_id     | INT(11)      | NO   | -   | -                | 路線ID |
| type_id      | INT(11)      | NO   | -   | -                | 種別ID |
| section_from | VARCHAR(40)  | NO   | -   | -                | 区間（始） |
| section_to   | VARCHAR(40)  | NO   | -   | -                | 区間（終） |
| fee          | INT(11)      | NO   | -   | -                | 料金 |
| note         | VARCHAR(100) | YES  | -   | NULL             | 訪問先 |
| is_active    | TINYINT(1)   | NO   | -   | 1                | 有効フラグ（論理削除） |
| created_at   | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 登録日時 |
| updated_at   | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 更新日時 |

### t_mail_recipients（メール宛先データ）
| Column      | Type          | Null | Key | Default           | Description |
|------------|--------------|------|-----|------------------|------------|
| id         | INT(11)      | NO   | PK  | AUTO_INCREMENT   | メールID |
| email      | VARCHAR(255) | NO   | UQ  | -                | メールアドレス |
| sort_order | INT(11)      | NO   | -   | -                | 表示順 |
| is_active  | TINYINT(1)   | NO   | -   | 1                | 有効フラグ（論理削除） |
| created_at | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 登録日時 |
| updated_at | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 更新日時 |

### t_routes（路線データ）
| Column      | Type          | Null | Key | Default           | Description |
|------------|--------------|------|-----|------------------|------------|
| id         | INT(11)      | NO   | PK  | AUTO_INCREMENT   | 路線ID |
| route_name | VARCHAR(40)  | NO   | UQ  | -                | 路線名 |
| sort_order | INT(11)      | NO   | -   | -                | 表示順 |
| is_active  | TINYINT(1)   | NO   | -   | 1                | 有効フラグ（論理削除） |
| created_at | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 登録日時 |
| updated_at | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 更新日時 |

### t_types（種別データ）
| Column      | Type          | Null | Key | Default           | Description |
|------------|--------------|------|-----|------------------|------------|
| id         | INT(11)      | NO   | PK  | AUTO_INCREMENT   | 種別ID |
| type_name  | VARCHAR(40)  | NO   | UQ  | -                | 種別名 |
| sort_order | INT(11)      | NO   | -   | -                | 表示順 |
| is_active  | TINYINT(1)   | NO   | -   | 1                | 有効フラグ（論理削除） |
| created_at | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 登録日時 |
| updated_at | TIMESTAMP    | NO   | -   | CURRENT_TIMESTAMP| 更新日時 |

## ER
```mermaid
erDiagram

    t_users {
        int id
        varchar name
        varchar email
        varchar password
        tinyint is_admin
        tinyint is_active
        timestamp created_at
        timestamp updated_at
    }

    t_expenses {
        int id
        int user_id
        date purchase_date
        int route_id
        int type_id
        varchar section_from
        varchar section_to
        int fee
        varchar note
        varchar cancel_reason
        tinyint is_active
        timestamp created_at
        timestamp updated_at
    }

    t_courses {
        int id
        int user_id
        varchar course_name
        int route_id
        int type_id
        varchar section_from
        varchar section_to
        int fee
        varchar note
        tinyint is_active
        timestamp created_at
        timestamp updated_at
    }

    t_routes {
        int id
        varchar route_name
        int sort_order
        tinyint is_active
    }

    t_types {
        int id
        varchar type_name
        int sort_order
        tinyint is_active
    }

    t_mail_recipients {
        int id
        varchar email
        int sort_order
        tinyint is_active
    }

    t_users ||--o{ t_expenses : ""
    t_users ||--o{ t_courses : ""

    t_routes ||--o{ t_expenses : ""
    t_routes ||--o{ t_courses : ""

    t_types ||--o{ t_expenses : ""
    t_types ||--o{ t_courses : ""
```

## License
MIT
