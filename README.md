vanilla-wordpress
=================

The official Vanilla Forums Wordpress plugin.

#Updating Vanilla-WordPress plugin using subversion

Vanilla-WordPress URL https://plugins.svn.wordpress.org/vanilla-forums.

WordPress uses subversion (SVN) which is a free open source version control.

##SVN Installation
```sh
brew install subversion
```
##SVN Client

TurtoiseSVN for Windows or SCPlugin for MAC.

##SVN Checkout

* Pick a directory on your local machine you want subversion to track.Ex. wordpress-plugin-public/vanilla-forums.

* To start tracking the project, we need to hook our local Vanilla-forums WordPress plugin. Right-click wordpress-plugin-public/vanilla-forums and choose svn checkout

**URL of the repository should be**

https://plugins.svn.wordpress.org/vanilla-forums

**Checkout directory should be** 

wordpress-plugin-public/vanilla-forums


Your subversion client will begin to pull all of the files as they currently stand on the subversion server.

Open wordpress-plugin-public/vanilla-forums you should see a .svn folder, branches, tags and trunk.

##Folders Strcuture

**Trunk**: contains the latest version of the plugin.

**Tags**: contains the public ready version which is a copy of the trunk.

##Pushing updates to SVN server

Copy your changes for the local Vanilla-wordpress plugin folder and paste it in the trunk folder, make sure you update the plugin version in readme.txt file.

Right click on wordpress-plugin-public/vanilla-forums and choose svn commit.

You will be asked to provide Vanilla's WordPress username and password.

*When you do a commit, the username to use is from readme.txt/Contributors, it is case-sensitive.*
##Tagging a version

Once you are ready for the public to use your plugin, you need to tag a version to set asside and leave alone.

Right click on wordpress-plugin-public/vanilla-forums/trunk and choose SVN Branch/Tag. 

In the "to path" add a /vanilla-forums/tags/version(ex. tags/1.1.18).





