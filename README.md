## AMySQLO
 - Library for asynchronous access to MySQL. Bases on [MySQLO](https://github.com/CloudMinePE/MySQLO).
---
### Examples:
 **Initialize connection:**
```php
$async = new AsyncMySQL(
    $this->getServer()->getTickSleeper(),
    new Credentials("127.0.0.1", "root", "root", "test"));
$async->start(
    2, //Thread count
    OldThread::class, //Thread class. For back compatible of PocketMineMP-API. OldThread to API 3, and NewThread to API 4. You can use DefaultThread for other CLI scripts.
    function(int $threadId) : void{}, //Called when thread successfully connect to your MySQL server
    function(int $threadId, ConnectionException $exception) : void{} //Called if thread cannot connect to mysql 
);
```
**Query:**
 - MySQLO allows convenient mapping of the result to entity classes:
```php
$async->query("SELECT * FROM `players` WHERE `nickname`='test' LIMIT 1")
    ->setOnSuccess(function(?User $user) : void{
        var_dump($user);
    })
    ->setOnError(function(MySQLOException $exception) : void{
        var_dump("[".$exception->getCode()."] ".$exception->getMessage());
    })
    ->executeSelectAndMapSingle(User::class); 
 ```
 - You can also split the result into two classes for join queries:
```php
$async->prepare("SELECT * FROM `stats` LEFT JOIN `regions` ON regions.owner=stats.user LIMIT :limit")
    ->setInt("limit", 1)
    ->setOnSuccess(function(?User $user, ?Region $region): void{
        var_dump($user, $region);
    })
    ->setOnError(function(MySQLOException $exception) : void{
        var_dump($exception);
    })
    ->executeSelectAndMapSingle(User::class, Region::class);
```
 - Also, you can ResultSet class for type-safe get result data:
```php
$async->query("SELECT * FROM `players` WHERE `nickname`='test' LIMIT 1")
    ->setOnSuccess(function(?ResultSet $resultSet) : void{
        if($resultSet !== null)
            var_dump($resultSet->getString("nickname"));
    })
    ->setOnError(function(MySQLOException $exception) : void{
        var_dump("[".$exception->getCode()."] ".$exception->getMessage());
    })
    ->executeSelectSingle();
```
**Prepared query:**
 - MySQLO provides basic prepared statement:
```php
$async->prepare("SELECT * FROM `players` WHERE `nickname`=:nickname LIMIT :limit")
    ->setString("nickname", "test")
    ->setInt("limit", 1)
    ->setOnSuccess(function(?ResultSet $resultSet) : void{
        if($resultSet !== null)
            var_dump($resultSet->getString("nickname"));
    })
    ->setOnError(function(MySQLOException $exception) : void{
        var_dump("[".$exception->getCode()."] ".$exception->getMessage());
    })
    ->executeSelectSingle();
```
**You can find all other information in the MySQLO repository**
