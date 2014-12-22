<?php

namespace djfm\ftr\tests\fixtures;

class ATestWithParallelDataProviderTest
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
	 * @parallelize
	 */
    public function testSomething($language, $country)
    {

    }

    public function testSomethingElse()
    {

    }
}
