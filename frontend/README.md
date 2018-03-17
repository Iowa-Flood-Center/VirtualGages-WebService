# Frontend

The main objective of this component is to provide users the access to the Virtual Gages data through a single and simplistic web service.

An additional tool was developed to continuously check and notify system managers regarding potential delays in data provision.

It was originally designed to be hosted by the IIHR Server 50.

## Setting up the environment

Git sparse checkout

Suppose that the backend will be hosted at ```[SYS-ROOT]/```.

Create the empty directory, move to there and initiate a git repository there:

```
$ mkdir [SYS-ROOT]/
$ cd [SYS-ROOT]/
$ git init
```

Add the Virtual Gages Web Service GitHub repository as the origin remote Git repository:

```
$ git remote add -f origin https://github.com/.../VirtualGages-WebService.git
```

Activate sparse checkout and limit the versioned content to the backend directory:

```
$ git config core.sparseCheckout true
$ echo 'frontend/' > .git/info/sparse-checkout
```

Retrieve the content from the server:

```
$ git pull origin master
```

## Setting up configuration files

Copy all the content from the ```conf-TEMPLATE/``` folder to the ```conf/``` directory.

Edit and rename all the files now in ```conf/``` following the guidelines provided in the *README.md* file located at the ```conf-TEMPLATE``` directory.