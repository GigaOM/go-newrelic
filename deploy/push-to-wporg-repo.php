#! php
<?php
// inspired by https://github.com/thenbrent 's git to svn deploy script
// http://thereforei.am/2011/04/21/git-to-svn-automated-wordpress-plugin-deployment/

// prevent execution if we're not on the command line
if ( 'cli' != php_sapi_name() )
{
	die;
}

// main config
$pluginslug = 'go-newrelic';
$svn_repo_path= '/tmp/'. $pluginslug; // path to a temp SVN repo. No trailing slash (be cautious about incorrect paths, note that we rm the contents later)
$svn_repo_url = 'http://plugins.svn.wordpress.org/' . $pluginslug . '/trunk/'; // Remote SVN repo with no trailing slash
$svn_ignore_files = array( // paths relative to the top of the svn_repo_path
	'README.md',
	'.git',
	'.gitignore',
	'.gitmodules',
	'deploy/',
);

// Let's begin...
echo "
Preparing to push $pluginslug to $svn_repo_url
";

echo '
Cleaning the destination path
';
passthru( "rm -Rf $svn_repo_path" );

echo "
Creating local copy of SVN repo at $svn_repo_path
";
passthru( "svn checkout $svn_repo_url $svn_repo_path" );


echo '
Prepping the SVN repo to receive the git
';
passthru( "rm -Rf $svn_repo_path/*" );

echo '
Exporting the HEAD of master from git to SVN
';
passthru( "git checkout-index -a -f --prefix=$svn_repo_path/" );


echo '
Exporting git submodules to SVN
';
passthru( "git submodule foreach 'git checkout-index -a -f --prefix=$svn_repo_path/\$path/'" );

echo '
Removing any svn:executable properties for security
';
passthru( "find $svn_repo_path -type f -not -iwholename *svn* -exec svn propdel svn:executable {} \; | grep 'deleted from'" );

echo '
Setting svn:ignore properties
';
passthru( "svn propset svn:ignore '" . implode( "\n", $svn_ignore_files ) ."
' $svn_repo_path
" );

passthru( "svn proplist -v $svn_repo_path" );

echo '
Marking deleted files for removal from the SVN repo
';
passthru( "svn st $svn_repo_path | grep '^\!' | sed 's/\!\s*//g' | xargs svn rm" );

echo '
Marking new files for addition to the SVN repo
';
passthru( "svn st $svn_repo_path | grep '^\?' | sed 's/\?\s*//g' | xargs svn add" );

echo '
Now forcibly removing the files that are supposed to be ignored in the svn repo
';
foreach( $svn_ignore_files as $file )
{
	passthru( "svn rm --force $svn_repo_path/$file" );
}


echo "
Automatic processes complete!

Next steps:

`cd $svn_repo_path` and review the changes
`svn commit` the changes
profit

* svn diff -x \"-bw --ignore-eol-style\" | grep \"^Index:\" | sed 's/^Index: //g' will be your friend if there are a lot of whitespace changes

Good luck!
";
