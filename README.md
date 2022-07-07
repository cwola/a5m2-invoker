# a5m2-invoker

PHPから'A5:SQL Mk-2'(A5M2)を簡単に実行するためのライブラリを提供します。

Provides a simple executor from PHP to 'A5:SQL Mk-2'(A5M2).

## Overview

PHPから'A5:SQL Mk-2'(A5M2)を簡単に実行するためのライブラリを提供します。

PDOでも、sshコマンドでトンネルを作っておくことで踏み台経由での接続が可能ですが、
その場合は、PDOの接続を切るまでの間バックグラウンドでポートフォワードをし続ける必要があることや、
その停止時にはプロセスを検索してキルする必要があることなどがネックです（複数プロセスが同時に動くと、プロセス番号を特定できなくなることもあります）  
A5:SQL Mk-2のコマンドラインユーティリティを使用すれば、そのような問題とは おさらば です。

A5:SQL Mk-2だとテーブル定義書(HTML)なども生成できるようなので、その辺りの実行機能も追加していく予定です。

Packagistへの公開は、ドキュメント作ってからにしようと思っています。

## Requirement
- PHP8.0+
- [cwola/attribute](https://github.com/cwola/attribute) v1.0+

## Document

詳細なドキュメントはココを参照してください(準備中)

## Preparation

- A5:SQL Mk-2(コマンドラインユーティリティ)をダウンロードします。

- jsonファイルに以下のような設定値を記載します。  
※ [Usage]に記載があるように、プログラム中から各設定値を追加することが可能なので設定ファイルが存在しなくても問題はありません。  
特にProviderなどは、タイプミスをしないようにプログラム上から定数(Configs::PROVIDER_xxx)で指定した方が良いでしょう。
各設定値の詳細は、[設定値一覧](https://github.com/cwola/a5m2-invoker/blob/main/doc/configs.md)を参照してください。

  - 例  
  ```
  {
    "executor":             "C:\\Program Files\\A5M2cmd\\A5M2cmd.exe",
    "connectionType":       "",
    "provider":             "",
    "userName":             "postgres",
    "password":             "",
    "direct":               true,
    "serverName":           "127.0.0.1",
    "port":                 5432,
    "ipVersion":            "v4",
    "useUnicode":           false,
    "initialSchemaName":    "",
    "database":             "main",
    "sshHostName":          "",
    "sshPort":              0,
    "sshDestHostName":      "",
    "sshDestPort":          0,
    "sshUserName":          "",
    "sshPassword":          "",
    "sshKeyFile":           "",
    "authentication":       "Server",
    "protocolVersion":      30
  }
  ```

## Usage

- Code Sample

```
<?php

use Cwola\A5M2\Invoker\Configs;
use Cwola\A5M2\Invoker\Executor;

/* 設定 */
$configs = new Configs(設定ファイルのパスを指定します(任意));

// 後から設定ファイルを読み込むことも可能です。
// 設定ファイルに存在しないプロパティは上書きされないので、設定ファイルの継承を疑似的に行うことが可能です。
$configs->load(__DIR__ . DIRECTORY_SEPARATOR . 'extends.json');

// 直接プロパティへ値をセットすることも可能です。
$configs->provider = Configs::PROVIDER_POSTGRES;
$configs->connectionType = Configs::CONNECTION_INTERNAL;


/* execute */
$executor = new Executor($configs);
$executor->defaultFetchMode = Executor::FETCH_ASSOC;
$executor->listType = 'Illuminate\\Support\\Collection';

if (!$executor->execute('SELECT * FROM foo WHERE status = :status;', ['status' => true])) {
    // outputsプロパティはdebugプロパティがtrueの場合にのみ設定されます
    throw new Exception(join("\n", $executor->outputs));
}

$result = $executor->fetch();
echo 'QUERY : ' . $executor->executedQuery . PHP_EOL;
echo 'TIME : ' . $executor->execTime . '[ms]' . PHP_EOL;
echo 'COUNT : ' . $executor->columnCount . PHP_EOL;
echo 'RESULT : ' . PHP_EOL;
var_dump($result);  // Illuminate\Support\Collection<array>

$result = $executor->fetch(Executor::FETCH_CLASS, 'stdClass');
echo 'RESULT : ' . PHP_EOL;
var_dump($result);  // stdClass[]
```

## Note

- プレースホルダについて  
  PreparedStatementのような名前付きプレースホルダ(:keyword)が使用可能ですが、これはエスケープ処理と単純な文字列置換でエミュレートされた**動的プレースホルダ**(のようなもの)です。  
  名前付きプレースホルダの名前には、半角英数とアンダースコア( _ )が使用できます。

  このライブラリは(コマンドラインユーティリティを使用するということから)、内製の小さな作業ツールに使用されることが想定されていますが、
  SQLインジェクションを厳密に回避する必要がある場合は、独自のエスケープ処理を施した文字列を使用するか、PDO等のライブラリを使用することも検討してください。

  プレースホルダは文字リテラルをシングルクォーテーション( ' )で囲みます。  
  したがって、エスケープ処理も(ほとんどのプロバイダで)シングルクォーテーションに対して働きますが、ダブルクォーテーションに対しては働きません。

  - プレースホルダの例
  ```
  $statement = prepare(
    Configs::PROVIDER_ORACLE,
    'SELECT * FROM foo WHERE name = :name AND danger = \'\\\\\\ \'aaa\\\' \\\\ :dummy \' AND secure = :secure status = :status;'
  );

  var_dump($statement->bindValues([
    'name' => 'name',
    'dummy' => 'no replace',
    'secure' => '\\\\\\ \'aaa\\\' \\\\ :status ',
    'status' => true
  ])->createRealStatement());

  // [outut]
  // string(129) "SELECT * FROM foo WHERE name = 'name' AND danger = '\\\ 'aaa\' \\ :dummy ' AND secure = '\\\ ''aaa\'' \\ :status ' status = true;"
  ```

  ---

- プレースホルダをサポートしていないプロバイダについて  
  [名前付きプレースホルダがサポートされていないプロバイダ](https://github.com/cwola/a5m2-invoker/blob/main/doc/pssupports.md)で"$executor->execute(string, array)"を実行(厳密には prepare 関数を実行)すると、以下のエラーが発生します。  
  ```
  sorry... :(, "prepared statement of '${provider}' is not yet supported in this version.
  ```
  そのような場合は、"$executor->execute(RawStatement, array)"を使用してください。  
  ```
  $statement = prepare('raw', 'SELECT * FROM foo WHERE status = :status;');
  $executor->execute(
    $statement,
    ['status' => [
      'value' => true,
      'type' => AbstractStatement::PARAM_BOOL
    ]]
  );
  ```
  RawStatementは、指定された値をエスケープせずに「生」のままA5:SQL Mk-2へ引き渡します。  
  したがって、プロバイダに依存せず使用することが可能ですが、SQLインジェクションの危険性がある場合はエスケープ処理を施すことを忘れないでください。  
  上述のコードのように、プレースホルダ値の型を明示的に指定することで、その型以外の入力を防ぐことが可能です(InvalidArgumentExceptionが発生します)。

  "いちいちRawStatementを準備するのが面倒だ" "他のプロバイダと同じように処理をしたい" と思われる方は、CoreSettings::$FORCE_SUPPORT_FOR_STATEMENTをtrueに設定することができます。  
  これによって、prepare関数にサポートしていないプロバイダが渡された時、prepare関数は例外ではなくRawStatementを返却するようになります。  
  ただしこれは推奨されません。  
  CoreSettings::$FORCE_SUPPORT_FOR_STATEMENTはグローバルスコープで影響するためです。

## Licence

[MIT](https://github.com/cwola/a5m2-invoker/blob/main/LICENSE)

[ソースの一部](https://github.com/cwola/a5m2-invoker/blob/main/src/Statement/PgsqlStatement.php)はPostgreSQLのコードを参考に作成されています。
