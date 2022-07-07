# Configsに設定できる設定値の一覧

| Name | Type | Required | Default | Description |
| --- | :---: | :---: | --- | --- |
| `executor` | string | 〇 | 空文字 | `A5:SQL Mk-2`の実行プログラムのパスを指定します。<br>コマンドラインユーティリティ版を指定してください。 |
| `connectionType` | string | - | `Configs::CONNECTION_INTERNAL` | `Configs::CONNECTION_INTERNAL`のみ。<br>OLEDBには対応していません。 |
| `provider` | string | 〇 | 空文字 | プロバイダ(`Configs::PROVIDER_xx`)を指定します。 |
| `userName` | string | 〇 | 空文字 | ユーザー名を指定します。 |
| `password` | string | - | 空文字 | パスワードを指定します。 |
| `direct` | bool | △ | false | TRUE : 直接接続<br>FALSE : OCI経由での接続<br>以下のプロバイダで使用します。<br>`Oracle` |
| `serverName` | string | △ | 空文字 | データベースサーバを指定します。<br>以下のプロバイダで使用します。<br>`Db2`, `SQL Server`, `PostgreSQL`, `MySQL`, `Interbase`<br>また、プロバイダが`Oracle`で直接接続である場合にも使用します。 |
| `port` | int | △ | 0 | ポート番号を指定します。<br>以下のプロバイダで使用します。<br>`Db2`, `SQL Server`, `PostgreSQL`, `MySQL`, `Interbase`<br>また、プロバイダが`Oracle`で直接接続である場合にも使用します。 |
| `ipVersion` | string | - | `Configs::IP_V4` | 利用するIPバージョン(`Configs::IP_xx`)を指定します。|
| `useUnicode` | bool | △ | false | TRUE : unicodeを使用する<br>FALSE : unicodeを使用しない<br>以下のプロバイダで使用します。<br>`Oracle`, `MySQL` |
| `initialSchemaName` | string | △ | 空文字 | 初期スキーマ名を指定します。<br>以下のプロバイダで使用します。<br>`Oracle`, `Db2`, `SQL Server`, `MySQL`, `PostgreSQL` |
| `database` | string | 〇 | 空文字 | 接続するデータベースを指定します。<br>`SQLite`, `Access`ではファイル名を指定してください。 |
| `sshHostName` | string | - | 空文字 | SSHホスト名を指定します。<br>`sshHostName`, `sshPort`, `sshUserName`, `sshPassword`が全て指定されている場合に使用可能です。 |
| `sshPort` | int | - | 0 | SSHポート番号を指定します。<br>`sshHostName`, `sshPort`, `sshUserName`, `sshPassword`が全て指定されている場合に使用可能です。 |
| `sshUserName` | string | - | 空文字 | SSHサーバでのユーザ名を指定します。<br>`sshHostName`, `sshPort`, `sshUserName`, `sshPassword`が全て指定されている場合に使用可能です。 |
| `sshPassword` | string | - | 空文字 | SSHサーバでのパスワードを指定します。<br>`sshHostName`, `sshPort`, `sshUserName`, `sshPassword`が全て指定されている場合に使用可能です。 |
| `sshDestHostName` | string | - | 空文字 | SSHホストからの転送先ホスト名を指定します。<br>`sshDestHostName`, `sshDestPort`が全て指定されている場合に使用可能です。 |
| `sshDestPort` | int | - | 0 | SSHホストからの転送先ポート番号を指定します。<br>`sshDestHostName`, `sshDestPort`が全て指定されている場合に使用可能です。 |
| `sshKeyFile` | string | - | 空文字 | SSH時に使用する秘密鍵ファイルのパスを指定します。<br>`sshHostName`, `sshPort`, `sshUserName`, `sshPassword`が全て指定されている場合に使用可能です。 |
| `authentication` | string | △ | 空文字 | 認証方法(`Configs::AUTHENTICATION_xx`)を指定します。<br>以下のプロバイダで使用します。<br>`MySQL` |
| `protocolVersion` | int | △ | 0 | プロトコルバージョン(`Configs::PROTOCOL_VERSION_xx`)を指定します。<br>以下のプロバイダで使用します。<br>`PostgreSQL`<br>version < 7.4 : Configs::PROTOCOL_VERSION_20<br>version >= 7.4 : Configs::PROTOCOL_VERSION_30 |
