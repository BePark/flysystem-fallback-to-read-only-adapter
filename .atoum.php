<?php

use mageekguy\atoum\reports;
use mageekguy\atoum\reports\coverage;
use mageekguy\atoum\writers\std;
use mageekguy\atoum\writers\file;
use mageekguy\atoum\report\fields\runner\result\logo;

//
//  config of tests
//

// branch coverage
$script->enableBranchAndPathCoverage();

// path to unit tests
$runner->addTestsFromDirectory(__DIR__ . '/tests/units');

if (in_array(getenv('TRAVIS_PHP_VERSION'), ['7.1', 'nightly'])) // temporary disable code coverage because of xdebug failure
{
	$script->noCodeCoverage();
}

//
// Reports
//
$report = $script->addDefaultReport();

$extension = new reports\extension($script);
$extension->addToRunner($runner);

// html report
$coverage = new coverage\html();
$coverage->addWriter(new std\out());
$coverage->setOutPutDirectory(__DIR__ . '/tests/reports/unit/');
$runner->addReport($coverage);

// telemetry
$telemetry = new reports\telemetry();
$telemetry->addWriter(new std\out());
$telemetry->readProjectNameFromComposerJson(__DIR__ . '/composer.json');
$runner->addReport($telemetry);

// atoum logo
$report->addField(new logo());

// clover coverage
$cloverWriter = new file(__DIR__ . '/tests/reports/clover.xml');
$cloverReport = new reports\asynchronous\clover();
$cloverReport->addWriter($cloverWriter);
$runner->addReport($cloverReport);

// coveralls
$token = getenv('COVERALLS_REPO_TOKEN') ?: null;
if ($token)
{
	$coverallsReport = new reports\asynchronous\coveralls('src', $token);

	$defaultFinder = $coverallsReport->getBranchFinder();
	$coverallsReport
		->setBranchFinder(
			function() use ($defaultFinder)
			{
				if (($branch = getenv('TRAVIS_BRANCH')) === false)
				{
					$branch = $defaultFinder();
				}

				return $branch;
			}
		)
		->setServiceName(getenv('TRAVIS') ? 'travis-ci' : null)
		->setServiceJobId(getenv('TRAVIS_JOB_ID') ?: null)
		->addDefaultWriter()
	;

	$runner->addReport($coverallsReport);
}
