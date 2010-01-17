-- 
-- Table structure for table `bids`
-- 

CREATE TABLE `bids` (
  `bid_id` int(11) NOT NULL auto_increment,
  `product_id` int(11) NOT NULL default '0',
  `username` varchar(32) NOT NULL default '',
  `time_of_bid` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `bid_amount` float NOT NULL default '0',
  `bid_status` enum('PENDING','APPROVED','REJECTED') NOT NULL default 'PENDING',
  PRIMARY KEY  (`bid_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- 
-- Dumping data for table `bids`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `closed`
-- 

CREATE TABLE `closed` (
  `product_id` int(11) NOT NULL default '0',
  `winner` varchar(32) NOT NULL default '',
  `bid_amount` float NOT NULL default '0',
  `email_sent` tinyint(1) NOT NULL default '0',
  `admin_email_sent` tinyint(4) NOT NULL default '0',
  `close_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `closed`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `config`
-- 

CREATE TABLE `config` (
  `auction_id` int(11) NOT NULL auto_increment,
  `title` varchar(64) default NULL,
  `admin_email` varchar(128) default NULL,
  `notification_from_address` varchar(128) default NULL,
  `closing_time` datetime default NULL,
  `bid_increment` float default NULL,
  `minimum_bid` float default NULL,
  `default_closing_time` datetime default NULL,
  `default_opening_time` datetime default NULL,
  `timezone` varchar(64) default NULL,
  `send_email_notifications` tinyint(1) default NULL,
  `send_outbid_notifications_to_admin` tinyint(1) default NULL,
  `winner_instructions` text,
  `reverse_auction` tinyint(1) unsigned NOT NULL default '0',
  `custom_header` text,
  `custom_footer` text,
  `custom_css` text,
  PRIMARY KEY  (`auction_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- 
-- Dumping data for table `config`
-- 

INSERT INTO `config` (`auction_id`, `title`, `admin_email`, `notification_from_address`, `closing_time`, `bid_increment`, `minimum_bid`, `default_closing_time`, `default_opening_time`, `timezone`, `send_email_notifications`, `send_outbid_notifications_to_admin`, `winner_instructions`, `reverse_auction`, `custom_header`, `custom_footer`, `custom_css`) VALUES (1, 'My Super Auction', 'admin@yourdomain.com', 'Auction <admin@yourdomain.com>', NULL, 5, 5, '2007-12-31 20:08:00', '2007-12-21 20:09:00', NULL, 1, 1, 'You have won the auction.', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

