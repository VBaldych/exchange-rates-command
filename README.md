# Exchange Rates Command

This is handy command for receiving exchange rates from your favorite bank (actually, you can choose PrivatBank or monobank) and notifiyng about rates changes. Info about received rates is keeping in related .json files. Files with previous exchange rates are located in `/files` folder.
In additional, this functionality covered by Unit tests.

## Installation

### Preparation

Containers are managed with docker-compose

In case you don't have it, you can find setup instructions for your OS distribution here: https://docs.docker.com/compose/install/

If you use Windows, it's better to install WSL: https://documentation.ubuntu.com/wsl/en/latest/guides/install-ubuntu-wsl2/

### Run local build

Initialize a local container using (First initialization)
```bash
make init
```

To start a local container run
```bash
make start
```

### Run application

Open docker container bash
```bash
make php-cli
```

Run application inside docker container 
```bash
php bin/console app:check-exchange-rates
```

or use alias
```bash
php bin/console app:cer
```

For checking emails go to Mailcatcher
```bash
http://localhost:8025/
```

### Run unit tests
For running Unit test please run the command
```bash
php bin/phpunit
```

## Execution scenarios

### 1. First fetch
1. Check folder `/files`, it should be empty;
2. Run the command, choose a bank and threshold;
3. In the console, you should see exchange rates list with success message; 
4. Check `/files` folder, file is created. Also, you can check how data is keeping in the file;
5. Go to Mailcather. You should see message with exchange rates.

### 2. Non-first fetch and same data
1. Run the command again, choose the same, type threshold;
2. In the console, you should see exchange rates list with message 'There no changes in exchange rates! No need to send mail'; 
3. Go to Mailcather. You shouldn't see any email.

### 3. Non-first fetch and changed data
1. Go to the related .json file in `/files` folder and change any buy/sell value
2. Run the command again, choose the same, type threshold;
3. In the console, you should see exchange rates list with message 'There are some rates changes: (currencies). The list of changes was sent via email!'; 
4. Go to Mailcather. You should see email with changed rates + actual list with all rates.

## Code analyzing
- For analyzing code with Rector use command
```bash
vendor/bin/rector process --dry-run
```