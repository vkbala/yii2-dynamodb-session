# yii2-dynamodb-session
yii2 session dynamodb extension is a Component to store session information in Amazon DynamoDB

This component is support Yii version >= 2.0

# Installing/Configuring

# Requirements

Download the AWS PHP SKD from http://aws.amazon.com/sdk-for-php/ and configure it in Yii2 framework
	
Added the AWS SDK folder/files in Yii project Vendor folder and add the folder in autoload in composer/autoload_psr4.php
	
	'Aws\\' => array($vendorDir . '/Aws'),	
	
# Yii2 Configuration
Setup the session components setting in Yii configuration file (config/web.php)

	'components' => [
		//.....
		//.....
		'session' => [		
			'class' => 'app\components\DynamoDbSession',
			'sessionTable' => 'session_log',
			'idColumn' => 'id',
			'dataColumn' => 'data',
			'expireColumn' => 'expire',
			'params' => [
				'key' => 'Your AWS ACCESS KEY',
				'secret' => 'Your AWS Secret Key',
				'region' => 'Your DynamoDB region', //us-west-2
			],
		],
		//......		
	]
	
# Integration 
Clone the extension from here using git
Move this DynamoDbSession.php file to your Yii components directory 
