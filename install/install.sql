--
-- Table structure for table 'bids'
--

CREATE TABLE 'bids' (
'bid_id' int(11) NOT NULL auto_increment,
'product_id' int(11) NOT NULL default '0',
'username' varchar(32 NOT NULL default '',
'time_of_bid' timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
'bid_amount' float NOT NULL default '0',
'bid_status' enum('PENDING','APPROVED','REJECTED') NOT NULL default 'PENDING',
PRIMARY KEY ('bid_id')
KEY 'product_id ('product_id')
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 AUTO_INCREMENT=4;

--
-- Dumping data for table 'bids'
--



----------------------------------------

--
-- Table structure for table 'closed'
--

CREATE TABLE 'closed' (
'product_id' int(11) NOT NULL default '0',
'winner' varchar(32) NOT NULL default '',
'bid_amount' float NOT NULL default '0',
'email_sent' tinyint(1) NOT NULL default '0',
'admin_email_sent' tinyint(4) NOT NULL default '0',
'close_date' timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY ('product_id')
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

__
__ Dumping data for table 'closed'
__