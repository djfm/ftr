<?php

namespace djfm\ftr\tests\fixtures;

class ATestWithDataProviderTest
{
	public function languageAndCountryPairs()
	{
		return [
			['fr', 'France'],
			['en', 'United States'],
			['es', 'Spain']
		];
	}

	/**
	 * @dataprovider languageAndCountryPairs
	 */
	public function testSomething($language, $country)
	{

	}

	public function testSomethingElse()
	{
		
	}
}