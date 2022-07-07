# 名前付きプレースホルダのサポート状況

| Provider | Support | Since | Description |
| --- | :---: | :---: | --- |
| `PROVIDER_ORACLE` | 〇 | v1.0.0 | ' を ' でエスケープします。 |
| `PROVIDER_DB2` | × | - | |
| `PROVIDER_SQL_SERVER` | 〇 | v1.0.0 | ' を ' でエスケープします。 |
| `PROVIDER_POSTGRES` | 〇 | v1.0.0 | ' を ' でエスケープします。<br>オプションにしたがって、追加のエスケープを行う可能性があります。 |
| `PROVIDER_MYSQL` | 〇 | v1.0.0 | NULL(0x00), \n, \r, \, ', ", \Z(0x1A) を \ でエスケープします。 |
| `PROVIDER_INTERBASE` | × | - | |
| `PROVIDER_SQLITE` | 〇 | v1.0.0 | ' を ' でエスケープします。 |
| `PROVIDER_ACCESS` | × | - | |
