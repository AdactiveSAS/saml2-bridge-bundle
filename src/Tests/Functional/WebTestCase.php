<?php

/**
 * Copyright 2017 Adactive SAS
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional;

use AdactiveSas\Saml2BridgeBundle\Tests\Functional\app\AppKernel;
use Liip\FunctionalTestBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class WebTestCase extends BaseWebTestCase
{
    public static function setUpBeforeClass()
    {
        static::deleteTmpDir();
    }

    public static function tearDownAfterClass()
    {
        static::deleteTmpDir();
    }

    protected static function deleteTmpDir()
    {
        if (!file_exists($dir = sys_get_temp_dir() . '/' . static::getVarDir())) {
            return;
        }
        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass()
    {
        require_once __DIR__ . '/app/AppKernel.php';

        return AppKernel::class;
    }

    protected static function createKernel(array $options = array())
    {
        $class = self::getKernelClass();
        if (!isset($options['test_case'])) {
            throw new \InvalidArgumentException('The option "test_case" must be set.');
        }

        return new $class(
            static::getVarDir(),
            $options['test_case'],
            isset($options['root_config']) ? $options['root_config'] : 'config.yml',
            isset($options['environment']) ? $options['environment'] : strtolower(static::getVarDir() . $options['test_case']),
            isset($options['debug']) ? $options['debug'] : true
        );
    }

    protected static function getVarDir()
    {
        return substr(strrchr(get_called_class(), '\\'), 1);
    }
}