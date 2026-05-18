# 旅費請求システム（Expense Management System）


## Overview
社内向けの旅費請求業務を効率化するためのWebアプリケーションです。  
ユーザーの申請から管理者のデータ管理を一元管理できる構成でPHP8を用いて開発しています。


## Demo
**URL**  
https://dolzap.conohawing.com/expense/

**Demo Account**
- USER：demo@example.com
- PASS：demo1234

**Note**
デモ環境では一部機能（管理機能・メール送信）に制限があります。  
データは毎日0時に自動リセットされます。


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
### Backend
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![Smarty](https://img.shields.io/badge/Smarty-Template%20Engine-orange)
![ADODB](https://img.shields.io/badge/ADODB-DB%20Abstraction-blue)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)

### Frontend
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)
![jQuery](https://img.shields.io/badge/jQuery-0769AD?logo=jquery&logoColor=white)
![Ajax](https://img.shields.io/badge/Ajax-Async%20Communication-lightgrey)

### Infrastructure
![Apache](https://img.shields.io/badge/Apache-D22128?logo=apache&logoColor=white)
![cron](https://img.shields.io/badge/cron-Scheduler-grey)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)

### Others
![Git](https://img.shields.io/badge/Git-F05032?logo=git&logoColor=white)
![GitHub](https://img.shields.io/badge/GitHub-181717?logo=github&logoColor=white)


## Directory
expense/
├── htdocs/
│   ├── index.php             # ルーティング（ユーザー）
│   ├── admin/                # ルーティング（管理）
│   ├── js/                   # JavaScript
│   ├── css/                  # CSS
│   └── img/                  # 画像
├── lib/
│   ├── conf/
│   │   └── Common.conf.php   # DB・定数設定
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


## Table


## License
MIT
