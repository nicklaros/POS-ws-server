POS-ws-server
=============

Websocket backend server for Next Gen POS

## Demo

site = http://pos.jawait.net

user = admin

pass = admin

## Requirements
 - [`Composer`](https://getcomposer.org/)
 - [`Propel ORM`](http://propelorm.org/)
 - [`Memcache`](http://memcached.org/)
 - [`Zero MQ`](http://zeromq.org/)
 - Httpd server (nginx, apache atau lainnya)
 - MariaDB atau MySql
 - PHP 5.4 +
 
## Frontend Repository
 
 [`POS - Next Generation Point Of Sale Application`](https://github.com/nicklaros/POS)
 
# Setting Up Project
 
 - Clone or fork POS-ws-server to your computer
 - Use `composer update` command in POS-ws-server root directory to tell composer to gather 
   required repository for you
 
# Running the Server
 
 Go to POS-ws-server root directory and use the following command
 
 ```bash
$ php bin/server.php
```

# Important Information

 - By default, POS will try to connect websocket at `ws://pos.localhost:8080`, so that means
   you have to set your POS development address to `http://pos.localhost` and make sure `port 8080`
   is open. Edit these two file if you want to change default setting
   - Frontend: `POS/app/fn/Util.js` on `line 19`
   
     ```bash
     Ext.ws.Main = POS.fn.WebSocket.create('ws://pos.localhost:8080/POS/Mains');
     ```
     
   - Backend: `POS-ws-server/bin/server.php` on `line 20`
   
     ```bash
     $app = new App('pos.localhost', 8080);
     ```
     
   Important to note that the address and port on each file above must be identical or the connection will fail!
 - Default MySql database configuration:
   - host: "localhost"
   - database: "pos"
   - user: "root"
   - password: "sqlpass"
   Edit these two file if you want to change default setting
   - Frontend: `POS/remote/propel-config.php`
   - Backend: `POS-ws-server/propel-config.php`
 