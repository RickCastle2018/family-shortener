<?php

$f3=require('lib/base.php');
$f3->set('DEBUG', 1);
if ((float)PCRE_VERSION<8.0)
	trigger_error('PCRE version is out of date');
$f3->config('config.ini');


$f3->route('GET /',
    function($f3) {
		$view = new View;
        echo $view->render('home.htm');
    }
);

$f3->route('GET|POST /newLink',
    function($f3) {

		$db = new DB\SQL(
			'mysql:host=localhost;port=3306;dbname=linker',
			'root',
			''
		);

		$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		function generate_string($input, $strength = 4) {
			$input_length = strlen($input);
			$random_string = '';
			for($i = 0; $i < $strength; $i++) {
				$random_character = $input[mt_rand(0, $input_length - 1)];
				$random_string .= $random_character;
			}
		
			return $random_string;
		}

		$link = $f3->get('POST.link');

		$check = new DB\SQL\Mapper($db,'links');
		$check->load(array('link="'. $link .'"'));
		if ($check->dry()) {
			$g_code = generate_string($permitted_chars);
			$row = new DB\SQL\Mapper($db,'links');
			$row->reset();
			$row->code = $g_code;
			$row->link = $link;
			$row->save();
		} else {
			$g_code = $check->code;
		}

		$short_link = 'https://'. $_SERVER['HTTP_HOST'] . '/' . $g_code;
		
		if (!empty($f3->get('POST'))) {
			$f3->set('link', $short_link);
			$f3->set('hits', $check->hits);
			$view = new View;
			echo $view->render('newLink.htm');
		} else {
			$f3->reroute('/');
		}
    }
);

$f3->route('GET /@code',
    function($f3) {
		$db = new DB\SQL(
			'mysql:host=localhost;port=3306;dbname=linker',
			'root',
			''
		);

		$code = $f3->get('PARAMS.code');

		$link = new DB\SQL\Mapper($db,'links');

		if ($link->load(array('code="'.$code.'"', 'link=?'))) {
			$link->hits++;
			$link->save();

			$f3->reroute($link->link);
		} else {
			$f3->reroute('/');
		}
    }
);

$f3->run();