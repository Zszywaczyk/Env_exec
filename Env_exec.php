<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
include('Net/SSH2.php');
include('Net/SFTP.php');
/*Usage*/
//php Env_exec.php git init
//php Env_exec.php git
//php Env_exec.php wp
//php Env_exec.php deploy
//php Env_exec.php deploy init

//TODO: add database control like: php Env_exec.php db pull OR php Env_exec.php db push
//TODO: github must contain deploy key for ssh deploy
//TODO: it probably generate lots of errors where we don't catch so todo CATCH

class Env_exec
{
    /*GITHUB DEV*/
    private $github_url       =    "";  //https://github.com/GIT_USR/GIT_REPO.git
    private $github_ssh       =    "git@github.com:GIT_USR/GIT_REPO.git";

    /*Production*/
    private $prod_ssh_host    =    "";                //ssh USER@ssh.example.com -p 22
    private $prod_ssh_user    =    "";
    private $prod_ssh_pass    =    "";
    private $prod_ssh_port    =    "";
    private $prod_host_url    =    "";  //url of host exmpl example.com
    private $prod_db_name     =    "";
    private $prod_db_user     =    "";
    private $prod_db_pass     =    "";
    private $prod_db_host     =    "";                   //DB_example: localhost
    private $prod_lang        =    "";                       //example: pl_PL
    private $prod_path        =    "";  //example: /var/www/example.com/public_html/

    /*Developmnet*/
    private $dev_host_url     =    "";  //url of host exmpl example.local
    private $dev_db_name      =    "";
    private $dev_db_user      =    "";
    private $dev_db_pass      =    "";
    private $dev_db_host      =    "";                   //DB_example: localhost
    private $dev_lang         =    "";                       //example: pl_PL

    public function __construct(){}
    public function getTime(){
        return date("m.d.y - H:i:s");
    }

    /*GIT EXECUTIVE DIRECTIVES*/
    public function git_init(){
        chdir('..');                 //change directory
        shell_exec('git init');     //execute command
        shell_exec('git remote add origin '. $this->github_url);
        chdir('env_exec');  //TODO: becouse of bug in git_exec where is going where git not installed ..
        $this->git_exec("Initial commit Env_exec");
    }
    public function git_exec(string $commit_msg){
        chdir('..');
        //git add .
        shell_exec("git add .");
        //git git commmit
        shell_exec('git commit -m "'.$commit_msg.'"' );
        //git push
        shell_exec('git push -u origin master');
    }
    /*================================GIT EXECUTIVE DIRECTIVES*/

    /*WORDPRESS CLI EXECUTIVE DIRECTIVES LOCAL*/
    /*================WORDPRESS CLI EXECUTIVE DIRECTIVES LOCAL*/

    /*DEPLOYMENT EXECUTIVE DIRECTIVES*/
    public function deploy_init(){
        $this->git_exec("before deploy " . $this->getTime());

        $ssh = new Net_SSH2($this->prod_ssh_host, $this->prod_ssh_port);
        if(!$ssh->login($this->prod_ssh_user, $this->prod_ssh_pass)){
            exit('SSH Login Failed');
        }

        $prepare = "cd ".$this->prod_path." ; "
            ."git init . ; "
            ."git remote add origin ".$this->github_ssh." ; "
            ."git pull origin master"." ; "
            ."git checkout";
        echo $ssh->exec($prepare);
        //echo $ssh->exec('cd /home/YOUR_USER/public_html ; mkdir');

        $ssh->disconnect();
    }
    public function deploy_exec(){
        $this->git_exec("before deploy " . $this->getTime());
        //TODO: remove redundant
        $ssh = new Net_SSH2($this->prod_ssh_host, $this->prod_ssh_port);
        if(!$ssh->login($this->prod_ssh_user, $this->prod_ssh_pass)){
            exit('SSH Login Failed');
        }

        echo $ssh->exec('cd '.$this->prod_path.' ; git pull origin master');

        $this->db_drop();
        $this->db_export_from_local();
        $this->db_send_sql();
        $this->db_create();
        $this->db_server_read_sql_file();

        $ssh->disconnect();
    }

    /*DB EXECUTIVE DIRECTIVES*/
    public function db_drop(){
        //mysql -u YOUR_DB_USER -p'YOUR_DB_PASS' -D YOUR_DB_NAME -e "DROP DATABASE YOUR_DB_NAME"
        $ssh = new Net_SSH2($this->prod_ssh_host, $this->prod_ssh_port);
        if(!$ssh->login($this->prod_ssh_user, $this->prod_ssh_pass)){
            exit('SSH Login Failed');
        }

        $prepare = "mysql ".$this->prod_db_user." -p'".$this->prod_db_pass."' -D ".$this->prod_db_name." -e ".'"DROP DATABASE '.$this->prod_db_name.'"';
        $ssh->exec($prepare);

        $ssh->disconnect();
    }
    public function db_export_from_local(){
        $prepare = 'wp search-replace "'.$this->dev_url.'" "'.$this->prod_url.'" --export=.\env_exec\local.sql  --precise --recurse-objects --all-tables';
        shell_exec($prepare);
    }
    public function db_send_sql(){
        $sftp = new NET_SFTP($this->prod_ssh_host, $this->prod_ssh_port);
        $sftp->login($this->prod_ssh_user, $this->prod_ssh_pass);
        $sftp->put('/home/YOUR_USER/mysql/local.sql', './env_exec/local.sql', NET_SFTP_LOCAL_FILE);
    }
    public function db_create(){
        $ssh = new Net_SSH2($this->prod_ssh_host, $this->prod_ssh_port);
        if(!$ssh->login($this->prod_ssh_user, $this->prod_ssh_pass)){
            exit('SSH Login Failed');
        }
        $prepare = "mysql -u".$this->prod_db_user." -p'".$this->prod_db_pass."' -e ".'"CREATE DATABASE '.$this->prod_db_name.'"';
        echo $ssh->exec($prepare);
        $ssh->disconnect();
    }
    public function db_server_read_sql_file(){
        //mysql db_name < script.sql > output.tab
        $ssh = new Net_SSH2($this->prod_ssh_host, $this->prod_ssh_port);
        if(!$ssh->login($this->prod_ssh_user, $this->prod_ssh_pass)){
            exit('SSH Login Failed');
        }
        //$prepare = "mysql -u".$this->prod_db_user." -p'".$this->prod_db_pass."' --sql-mode=\"\" ".$this->prod_db_name.' < /home/YOUR_USER/mysql/local.sql';
        //$prepare = "mysql -u".$this->prod_db_user." -p'".$this->prod_db_pass."' {$this->prod_db_name} -e ".'" USE '.$this->prod_db_name.'; set sql_mode = ""; source /home/YOUR_USER/mysql/local.sql "';
        $prepare = "mysql -u {$this->prod_db_user} -p'{$this->prod_db_pass}' -e \" USE {$this->prod_db_name}; set sql_mode=''; source /home/YOUR_USER/mysql/local.sql; \" ";
        echo $ssh->exec($prepare);
        $ssh->disconnect();
    }
    /*================DB EXECUTIVE DIRECTIVES*/

}

$env_exec = new Env_exec();

if($argv[1] === 'git'){
    if(!empty($argv[2]) && $argv[2] === 'init'){
        $env_exec->git_init();
    }
    elseif (!empty($argv[2])){
        $env_exec->git_exec($argv[2]);
    }
    else{
        $env_exec->git_exec("Generic commit: " . $env_exec->getTime());
    }
}

elseif ($argv[1] === 'wp'){
    echo "wp init";
}

elseif($argv[1] === 'deploy' ){
    if(!empty($argv[2]) && $argv[2] === 'init'){
        $env_exec->deploy_init();
    }
    else{
        $env_exec->deploy_exec();
    }
}

else{
    echo "[Env_exec]: \tUndefined command!";
}