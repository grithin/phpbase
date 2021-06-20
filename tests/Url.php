<?php

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Url;

/**
* @group Url
*/
class UrlClassTest extends TestCase{
	function test_resolve_relative(){
		#+ test base path alone {
		$this->assertEquals('bob.com', Url::resolve_relative('bob.com'), 'simple domain');
		$this->assertEquals('/bob', Url::resolve_relative('/bob'), 'simple domain');
		$this->assertEquals('/bill', Url::resolve_relative('/bob/../bill'), 'single relative');
		$this->assertEquals('/bill', Url::resolve_relative('/bob/../..//bill'), 'dead end relative 1');
		$this->assertEquals('/bill', Url::resolve_relative('/../../bill'), 'dead end relative 2');
		$this->assertEquals('//bob.com/bill', Url::resolve_relative('//bob.com/../../bill'), 'dead end relative 2');
		$this->assertEquals('image://bob.com/bill', Url::resolve_relative('image://bob.com/../../bill'), 'dead end relative 2');
		#+ }

		#+ test base path with relative path {
		$this->assertEquals('/bob', Url::resolve_relative('/bill/', '../bob'), 'base, relative');
		$this->assertEquals('/bob', Url::resolve_relative('/bill/', '../../bob'), 'base, relative dead end');
		$this->assertEquals('//bob.com/bob', Url::resolve_relative('//bob.com/bill/', '../../bob'), 'base, relative dead end on base domain');
		$this->assertEquals('//bill.com/bob', Url::resolve_relative('//bob.com/bill/', '//bill.com/bob'), 'base, domain swap');
		$this->assertEquals('//bill.com/bob', Url::resolve_relative('//bob.com/bill/', '//bill.com/../bob'), 'base, domain swap with dead end');
		$this->assertEquals('//bob.com/bob', Url::resolve_relative('//bob.com/bill/sue', '/bob'), 'base, absolute path');
		$this->assertEquals('//bob.com/bob', Url::resolve_relative('//bob.com/bill/sue', '/../bob'), 'base, absolute path with relative');
		#+ }

		# test non-path part preservation
		$this->assertEquals('/bob?bill=sue#one', Url::resolve_relative('/bill/?moe=joe', '../bob?bill=sue#one'), 'base, relative');
	}

}