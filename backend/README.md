# Backend

This component is responsible for pre-processing all data displayed by the *frontend* component of the system.

Internal steps include:

- importing raw modeled and observed data;
- pre-processing and converting data;
- submitting the data to the *frontend* server.

It was originally designed to be hosted by the IIHR Server-54.

## Setting up the environment

### Git sparse checkout 

Suppose that the *backend* will be hosted at ```[SYS-ROOT]/```.

Create the empty directory, move to there and initiate a git repository there:


```
$ mkdir [SYS-ROOT]/
$ cd [SYS-ROOT]/
$ git init
```

Add the IHMIS GitHub repository as the *origin* remote Git repository:

```
$ git remote add -f origin https://github.com/.../VirtualGages-WebService.git
```

Activate sparse checkout and limit the versioned content to the *backend* directory:
```
$ git config core.sparseCheckout true
$ echo 'backend/' > .git/info/sparse-checkout
```

### Setting up configuration files

Copy all the content from the `conf-TEMPLATE/` folder to the `conf/` directory.

**TODO: continue**