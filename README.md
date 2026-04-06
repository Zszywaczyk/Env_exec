# Env_exec
## Overview
Automate Wordpress Theme deploy with github repository and ssh

## How it works


## Instruction 
#### 1. Download Env_exec

#### 2. Create GitHub Repository and Public Key
   1. Create GitHub repository
   2. Go to repository Settings >> Deploy keys >> Add new 
   3. To generate key go to remote server and paste this command:
      ```
      ssh-keygen -t rsa -b 4096 -C "<put-your-email>"
      ```
      (you can skip all questions and just `enter` them)
   4. Check where have file saved. Look for "Your public key has been saved in <path>/id_rsa.pub"
   5. Execute cat and copy returned key
      ```
      cat <path>/id_rsa.pub
      ```
   6. Past key where we eneded step 2. (full key with email)
   7. There is no need to check 'Allow write access' becouse Env_exec only pull master
   
#### 3. Edit variables in Env_exec.php
   
#### 4. Remote edit
   1. Add or Edit existing file `~/.ssh/config`, copy paste:
      ```
      Host github.com 
        HostName github.com 
        IdentityFile ~/.ssh/id_rsa
      ```
   
   Links:
   - https://gist.github.com/zhujunsan/a0becf82ade50ed06115
   - https://docs.github.com/en/developers/overview/managing-deploy-keys
   
