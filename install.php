<html>
	<body>
		<h1>Bid4it installation instructions</h1>
		
		<h2>System requirments</h2>
		<ol>
			<li>PHP 4.3 or higher with short tags on</li>
			<li>MySQL 4.1 or higher</li>
		</ol>

		<p>Before you can use bid4it! you need to set up the database and edit conf.ini.</p>

		<h2>1. Create the MySQL database and user</h2>
		<p>Create a database in your MySQL server for bid4it. Then create a MySQL user that bid4it! can
		use to access the database. The user account must have full access privileges to this database.</p>
		
		<h2>2. Create tables in the database</h2>
		<p>Run the following MySQL commands to set up the Web Auction database.</p>
		<textarea style="width: 100%; height: 200px;"><?php include 'install/install.sql'?></textarea>
		
		<h2>3. Edit conf.ini</h2>
		<p>Update the <em>[_database]</em> section of the conf.ini file so that it contains the connection 			information to the database created in step 1.</p>
		
		<h2>4. File Permissions</h2>
		<ol>
			<li>Make the <em>templates_c</em> directory writable by the web server. The 				<a href="http://smarty.php.net">Smarty</a> templates are cached here.</li>
			
			<li>Make the <em>tables/products/product_image</em> directory writable by the 
			server. Images for the auction items will be stored here.
			</li>
		</ol>
		
		<h2>5. Log In</h2>
		<p>Bid4it should now be available <a href="index.php">here</a>.
		Log into the administration section <a href="index.php?-action=login">here</a> with the username 			<em>admin</em> and password <em>password</em>.
		</p>
		
		<h1>Problems</h1>
		<h2>Support</h2>
		<p>If you run into problems with the installation, please visit <a href="http://github.com/josefnankivell/bid4it/" target="_blank">the project website</a>.</p>

	</body>

</html>
