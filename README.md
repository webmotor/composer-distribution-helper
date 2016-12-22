# Composer security tool
It will help to protect your account during production release on third parties servers.

Problem is described here: https://github.com/composer/composer/issues/5689

This plugin provides two commands, that should be executed after release stage:

 - *distribution:clean-vcs* - will remove all git repositories in vendor directory.
 - *distribution:clean-vcs-passwords* - will remove account information from git repositories in vendor dir.

Requires composer:^1.2.0
