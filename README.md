# PHPStreamServer runtime for symfony applications
![PHP >=8.2](https://img.shields.io/badge/PHP->=8.2-777bb3.svg?style=flat)
![Symfony ^7.0](https://img.shields.io/badge/Symfony-^7.0-374151.svg?style=flat)
[![Version](https://img.shields.io/github/v/tag/luzrain/phpstreamserver-bundle?label=Version&filter=v*.*.*&sort=semver&color=374151)](../../releases)
[![Tests Status](https://img.shields.io/github/actions/workflow/status/luzrain/phpstreamserver-bundle/tests.yaml?label=Tests&branch=master)](../../actions/workflows/tests.yaml)

This bundle provides a [PHPStreamServer](https://github.com/luzrain/phpstreamserver) integration with Symfony framework to run your application in a highly efficient event-loop based runtime.  

## Getting started
### Install composer packages
```bash
$ composer require luzrain/phpstreamserver-bundle
```

### Enable the bundle
```php
<?php
// config/bundles.php

return [
    // ...
    Luzrain\PHPStreamServerBundle\PHPStreamServerBundle::class => ['all' => true],
];
```

### Configure the bundle
A minimal configuration might look like this.  
For all available options with documentation, see the command output.
```bash
$ bin/console config:dump-reference phpstreamserver
```

```yaml
# config/packages/phpstreamserver.yaml

phpstreamserver:
  servers:
    - name: 'Webserver'
      listen: http://0.0.0.0:80
      processes: 4
```

### Start application
```bash
$ APP_RUNTIME=Luzrain\\PHPStreamServerBundle\\Runtime php public/index.php start
```

\* For better performance, install the _php-uv_ extension.

## Reload strategies
Because of the asynchronous nature of the server, the workers reuse loaded resources on each request.
This means that in some cases we need to restart workers.
For example, after an exception is thrown, to prevent services from being in an unrecoverable state.
Or every time you change the code in the IDE in dev environment.  
The bundle provides several restart strategies that can be configured depending on what you need.

- **on_exception**  
  Reload worker each time that an exception is thrown during the worker lifetime.
- **on_each_request**  
  Reload worker after each http request. This strategy is for debug purposes.
- **on_ttl_limit**  
  Reload worker after TTL lifiteme will be reached. Can be used to prevent memory leaks.
- **on_requests_limit**  
  Reload worker on every N request.
- **on_memory_limit**  
  Reload worker after memory usage exceeds threshold value.
- **on_file_change**  
  Reload all workers each time that monitored files are changed. **  

** It is highly recommended to install the _php-inotify_ extension for file monitoring. Without it, monitoring will work in polling mode, which can be very cpu and disk intensive for large projects.

See all available options for each strategy in the command output.
```bash
$ bin/console config:dump-reference phpstreamserver reload_strategy
```

```yaml
# config/packages/phpstreamserver.yaml

phpstreamserver:
  reload_strategy:
    on_exception:
      active: true

    on_file_change:
      active: true
```

## Scheduler
Periodic tasks can schedule the execution of external programs as well as internal Symfony application commands.  
To run a Symfony command, simply type the command name without any prefixes.  
Schedule string can be formatted in several ways:  
- An integer to define the frequency as a number of seconds. Example: _60_
- An ISO8601 datetime format. Example: _2024-02-14T018:00:00+08:00_
- An ISO8601 duration format. Example: _PT1M_
- A relative date format as supported by DateInterval. Example: _1 minutes_
- A cron expression**. Example: _*/1 * * * *_

** Note that you need to install the [dragonmantank/cron-expression](https://github.com/dragonmantank/cron-expression) package if you want to use cron expressions as schedule strings

```yaml
# config/packages/phpstreamserver.yaml

phpstreamserver:
  tasks:
    # Runs external program every 15 seconds
    - name: 'Task 1'
      schedule: '15 second'
      command: '/bin/external-program'

    # Runs symfony command as a task every minute
    - name: 'Task 2'
      schedule: '*/1 * * * *'
      command: 'app:my-task-command'
```

## Supervisor
Supervisor can keep processes alive and wake up when one of them dies.  
It can also work with both external commands and internal Symfony commands.  
To run a Symfony command, simply type the command name without any prefixes.  

```yaml
# config/packages/phpstreamserver.yaml

phpstreamserver:
  processes:
    # Runs external program
    - name: 'External process'
      command: '/bin/external-program'
      count: 1

    # Runs symfony command
    - name: 'Symfony command process'
      command: 'messenger:consume queue --time-limit=600'
      count: 4
```
