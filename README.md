# legacy

                         _____  _____
                        <     `/     |
                         >          (
                        |   _     _  |
                        |  |_) | |_) |
                        |  | \ | |   |
                        |            |
         ______.______%_|            |__________  _____
       _/                                       \|     |
      |               N E R A H I K A D A              <
      |_____.-._________              ____/|___________|
                        |            |
                        |            |
                        |            |
                        |            |
                        |   _        <
                        |__/         |
                         / `--.      |
                       %|            |%
                   |/.%%|          -< @%%%
                   `\%`@|     v      |@@%@%%
                 .%%%@@@|%    |    % @@@%%@%%%%
            _.%%%%%%@@@@@@%%_/%\_%@@%%@@@@@@@%%%%%%

## MEMO
### AntiCheat_v1.0.0
今までになかった方法(多分)で飛行を検出します

### AntiCheat_v2
(特別なスキンが使えない、スパム検出)だけです<br>
スキンの保存機能があります。うまく抜き出して使ってください

### AntiCheat_v3.1.9
今までになかった方法(多分)で Hitbox, Aimbot を検出します<br>
その他に、MCPE Proxyを弾いたり、 編集すれば、特別なスキンを使えなくさせたりできます<br>
何故か飛行は消しました('ω')

### Face
顔を保存します

### LoginSystem
Sorrow Server時代に使っていたログインシステムです<br>
**※ SQL Injection の対策がされていません**<br>
**※ データベースの構成が分かりません**

### Game
Sorrow Serverのメインプラグインです<br>
なんか無駄にKillAura検出機能が入っています<br>
ワールドと一緒にどうぞ<br>
Waitinglobby2がロビー、 hubがゲームワールドです<br>
**※ データベースの構成が分かりません**

### conversion
PC から PE へブロックIDを変換するプラグインです<br>
サーバーに入ると変換が始まります<br>
**※ 一部のブロックに対応していません(新しいブロック、 コンクリートなど)**<br>
**※ クソコです**

### Copy
ブロックをコピーします<br>
使い方に癖があります<br>
一度どのような感じでコピーされるか確かめてから使った方がいいです<br>
Undo機能は無いので注意<br>
通常コピーはマルチタスクに対応させようとして失敗しました。治ってません<br>
<br>使い方:<br>
**/bc <1|2>** : コピー元座標を設定します<br>
**/bc 3** : コピー先の座標を設定します<br>
**/bc nc** : 通常コピー<br>
**/bc rc** : 180°回転させてコピー

### AI
[https://youtu.be/ToOco0jZepM](https://youtu.be/ToOco0jZepM)<br>
YouTubeで公開してるクソAIです

### ChunkInfo
チャンクの座標(?)を表示します<br>
NBTExploer 使うときに便利です

### Danmaku
なんか弾幕ができます

### RobotArm
[https://youtu.be/QPUFckIyEKQ](https://youtu.be/QPUFckIyEKQ)<br>
これを再現しました<br>
※リソースパックも一緒にお使いください

### SkyWars
SkyWars プラグインです<br>
ワールドも一緒にどうぞ (hypixel-lobby をデフォルトワールドにしてください)<br>
( データベースを実装しようとした痕跡があります('ω') )

### Ban_v1
SkyWars 時代のBanプラグインです<br>
**※ SQL Injection の対策がされていません**<br>

<br>MySQL Setup: 
```
CREATE TABLE players(name VARCHAR(15), ip VARCHAR(15), cid BIGINT);
CREATE TABLE ban(id VARCHAR(13), name VARCHAR(15), ip VARCHAR(15), cid BIGINT, banned BOOL, reason TEXT, time BIGINT, forever BOOL, `limit` INT);
```

<br>使い方:<br>
**/ban <Name> <Reason> <数字|f>** : プレイヤーをBanします<br>
最後のパラメーターの数字は、期限Banをする時に指定します (秒)<br>
無期限(永遠)の場合は f を指定してください<br>
コマンドでスペースを打ちたいときは \\s を入力してください (例: Nera hikada -> Nera\\shikada)

### Ban_v2.4.0
最新 Banプラグインです<br>
Banされていたら別のサーバーへ転送する機能付きです<br>
転送先のサーバーでは Banned プラグインを使用することをおススメします<br>

<br>MySQL Setup: 
```
CREATE TABLE IF NOT EXISTS `players`(
	`xuid` VARCHAR(20),
	`name` VARCHAR(15),
	`ip` VARCHAR(15),
	`cid` BIGINT
);

CREATE TABLE IF NOT EXISTS `ban`(
	`id` VARCHAR(13),
	`xuid` VARCHAR(20),
	`ip` VARCHAR(15),
	`cid` BIGINT,
	`banned` BOOLEAN,
	`reason` TEXT,
	`time` BIGINT,
	`forever` BOOLEAN,
	`limit` INT,
	`by` TEXT
);
```

<br>使い方:<br>
Ban_v1 と一緒です<br>
スペースを入力したい場合はダブルクオーテーション(")で囲ってください (例: "Nera hikada")<br>
**/banlist** : Banされたプレイヤーのリストを表示します<br>
(自分は誤Banしないつもりでいたので、 /pardon コマンドは実装されていません。 すみません)

### Banned
Ban_v2.4.0 の転送先のサーバーに入れるプラグインです<br>
サーバーに入った時に、Banされた理由、残り時間が表示されます

### Rank
ランクの機能を追加するプラグインです<br>
Cape(マント)、 pngからプラグイン内で使える形式へ変換するphpスクリプト付きです<br>
実装されているランクは、 Owner, Sponsor, Admin, YT(4種類) です<br>
RankId: Owner(**1**), Sponsor(**2**), Admin(**3**), (金色+太字)YT(**4**), (金色)YT(**5**), (灰色+太字)YT(**6**), (灰色+太字)YT(**7**)<br>

<br>MySQL Setup: 
```
CREATE TABLE IF NOT EXISTS `rank`(
	`xuid` VARCHAR(20),
	`rank` INT UNSIGNED
);
```

<br>使い方<br>
**/rank set <Name> <RankId>** : プレイヤーのランクをセットします (0にすると削除されます)<br>
(スペースを含む場合はダブルクオーテーション(")で囲ってください)<br>
**/rank list** : ランクのリストを表示します<br>

<br>pngからプラグイン内で使える形式へ変換するphpスクリプトの使い方<br>
c.php と同じディレクトリに変換したいファイルを**in.png**という名前で配置<br>
gbライブラリがあるphpバイナリーで `php c.php` を実行<br>
out というファイルが出来るので、Owner.php などを参考に頑張ってください('ω')

### evalPlugin
サーバー内で任意のコードを実行できるプラグインです (開発者向け)<br>
**使う前に、必ずファイルを編集して、権限設定を確認してください**<br>
コンソール、もしくはチャット欄から実行できます<br>
`/*e*/`を加えると、コードとして判定されます<br>
例: `EXAMPLE CODE; /*e*/`

## 注意
一部のプラグインは
 - 最新版に対応させていない (3.0.0-ALPHA12 ってなってれば最新版に対応してます)
 - 重大な脆弱性がある
 - データベースの構成が分からない

<br>です<br>
誰か優しい人直して…