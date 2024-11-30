<?php


return [
	'kafka' => [
		'sync_topic' => env('KAFKA_SYNC_TOPIC'),
		'connection' => [
			'global' => [
				'group.id' => env('KAFKA_GROUP_ID'),
				'metadata.broker.list' => env('KAFKA_BROKERS'),
				'enable.auto.commit' => 'false',
//				'sasl.username' => env('KAFKA_SYNC_PASSWORD'),
//				'sasl.password' => env('KAFKA_SYNC_USERNAME'),
//				'sasl.mechanism' => env('SASL_MECHANISM'),
//				'sasl.jaas.config' => env('SASL_JAAS_CONFIG'),
//				'security.protocol' => env('SECURITY_PROTOCOL'),
			
			],
			'topic' => ['auto.offset.reset' => 'beginning',],
		]
	],
];
