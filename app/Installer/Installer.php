<?php

namespace App\Installer;

use App\Exceptions\formValidationException;
use App\Exceptions\formValidationFieldError;
use Illuminate\Support\Facades\Artisan;
use \Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class Installer {

    /**
     * Contains the installer steps in the correct order of execution
     * @var array[]
     */
    private array $installSteps;

    /**
     * This array defines the valid fields for each database type.
     * As well as relevant information such as the validation requirements, type, dotconfig and dotenv keys.
     * @var string[][][]
     */
    private array $dbValues;

    public function __construct()
    {
        $this->installSteps = [
            [
                'name' => 'Database migrations',
                'description' => 'Creates tables and indexes in the database',
                'slug' => 'migrate-database',
                'running' => false,
                'status' => -1,
                'method' => 'migrateDatabase'
            ],
            [
                'name' => 'Generate Encryption Keys',
                'description' => 'creates the encryption keys needed to generate secure access tokens',
                'slug' => 'generate-keys',
                'running' => false,
                'status' => -1,
                'method' => 'installPassport'
            ],
            [
                'name' => 'Database seeding',
                'description' => 'Adds initial data to the database',
                'slug' => 'seed-database',
                'running' => false,
                'status' => -1,
                'method' => 'seedDatabase'
            ],
            [
                'name' => 'Finishing up',
                'description' => 'Finalize the installation',
                'slug' => 'finish-install',
                'running' => false,
                'status' => -1,
                'method' => 'finishInstall'
            ]
        ];

        $this->dbValues = [
            'mysql' => [
                'host' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.mysql.host',
                    'dotenv' => 'DB_HOST',
                    'type' => 'text',
                    'default' => 'localhost'
                ],
                'port' => [
                    'validate' => 'required|numeric|integer',
                    'dotconfig' => 'database.connections.mysql.port',
                    'dotenv' => 'DB_PORT',
                    'type' => 'text',
                    'default' => '3306'
                ],
                'database' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.mysql.database',
                    'dotenv' => 'DB_DATABASE',
                    'type' => 'text',
                    'default' => 'shoutzor'
                ],
                'username' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.mysql.username',
                    'dotenv' => 'DB_USERNAME',
                    'type' => 'text',
                    'default' => 'shoutzor'
                ],
                'password' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.mysql.password',
                    'dotenv' => 'DB_PASSWORD',
                    'type' => 'password',
                    'default' => ''
                ]
            ],
            'pgsql' => [
                'host' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.pgsql.host',
                    'dotenv' => 'DB_HOST',
                    'type' => 'text',
                    'default' => 'localhost'
                ],
                'port' => [
                    'validate' => 'required|numeric|integer',
                    'dotconfig' => 'database.connections.pgsql.port',
                    'dotenv' => 'DB_PORT',
                    'type' => 'text',
                    'default' => '5432'
                ],
                'database' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.pgsql.database',
                    'dotenv' => 'DB_DATABASE',
                    'type' => 'text',
                    'default' => 'shoutzor'
                ],
                'username' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.pgsql.username',
                    'dotenv' => 'DB_USERNAME',
                    'type' => 'text',
                    'default' => 'shoutzor'
                ],
                'password' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.pgsql.password',
                    'dotenv' => 'DB_PASSWORD',
                    'type' => 'password',
                    'default' => ''
                ]
            ],
            'sqlsrv' => [
                'host' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.sqlsrv.host',
                    'dotenv' => 'DB_HOST',
                    'type' => 'text',
                    'default' => 'localhost'
                ],
                'port' => [
                    'validate' => 'required|numeric|integer',
                    'dotconfig' => 'database.connections.sqlsrv.port',
                    'dotenv' => 'DB_PORT',
                    'type' => 'text',
                    'default' => '1433'
                ],
                'database' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.sqlsrv.database',
                    'dotenv' => 'DB_DATABASE',
                    'type' => 'text',
                    'default' => 'shoutzor'
                ],
                'username' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.sqlsrv.username',
                    'dotenv' => 'DB_USERNAME',
                    'type' => 'text',
                    'default' => 'shoutzor'
                ],
                'password' => [
                    'validate' => 'required|string',
                    'dotconfig' => 'database.connections.sqlsrv.password',
                    'dotenv' => 'DB_PASSWORD',
                    'type' => 'password',
                    'default' => ''
                ]
            ]
        ];
    }

    /**
     * Returns the installation steps
     * @return array[]
     */
    public function getSteps(): array {
        return $this->installSteps;
    }

    /**
     * Returns the valid database fields that can be used for configuring the database.
     * These fields contain information such as what their relevant dotconfig and dotenv values are
     * @return string[][][]
     */
    public function getDbFields(): array {
        return $this->dbValues;
    }

    /**
     * Tests & Configures the SQL settings to use
     * @param  string  $dbtype
     * @param  string  $host
     * @param  string  $port
     * @param  string  $database
     * @param  string  $username
     * @param  string  $password
     * @return InstallStepResult
     */
    public function configureSql(string $dbtype, string $host, string $port, string $database, string $username, string $password): InstallStepResult
    {
        $settingParams = [
            'dbtype' => $dbtype,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password
        ];

        $success = true;
        $exception = null;

        // Create a validator that checks if a valid database type has been provided
        $dbTypeValidator = Validator::make($settingParams, [
            'dbtype' => [
                'required',
                'string',
                Rule::in(array_keys($this->dbValues))
            ]
        ]);

        // Validate the provided database type
        if ($dbTypeValidator->fails()) {
            $validationErrors[] = new formValidationFieldError('dbtype', 'Invalid database type provided');
            return new InstallStepResult(false, '', new formValidationException($exception));
        }

        // Select the fields to use based on the selected database type
        $selectedDbValues = $this->dbValues[$settingParams['dbtype']];

        // Validate the provided values
        $errors = Validator::make($settingParams, array_map(function ($item) {
            return $item['validate'];
        }, $selectedDbValues));

        $errors = $errors->errors()->getMessages();
        $errors = array_map(function ($item) {
            return $item[0];
        }, $errors);

        // Check if any validation errors occurred
        if (count($errors) > 0) {
            foreach($errors as $name=>$error) {
                $validationErrors[] = new formValidationFieldError($name, $error);
            }

            return new InstallStepResult(false, '', new formValidationException($exception));
        }

        // Create an array for the new config values
        $dotConfigValues = [];
        $dotEnvValues = [];
        foreach ($selectedDbValues as $name => $item) {
            $dotConfigValues[$item['dotconfig']] = $settingParams[$name];
            $dotEnvValues[$item['dotenv']] = $settingParams[$name];
        }

        // Load the new config values in the current session
        config($dotConfigValues);

        // Test database connection
        try {
            DB::connection()->getPdo();
            $result['connection'] = true;

            //Write the values to the .env file
            $editor = DotenvEditor::load();
            $editor->setKey('DB_CONNECTION', $settingParams['dbtype'])
                   ->setKeys($dotEnvValues)
                   ->save();

            # Clear the cache config
            Artisan::call('config:cache');
        } catch (Exception $e) {
            $exception = $e;
        }

        return new InstallStepResult($success, Artisan::output(), $exception);
    }

    /**
     * Executes the artisan migrate command
     * @return InstallStepResult
     */
    public function migrateDatabase(): InstallStepResult
    {
        $success = true;
        $exception = null;

        try {
            # Execute the database migrations
            Artisan::call('migrate --force');
        } catch (Exception $e) {
            $success = false;
            $exception = $e;
        }

        return new InstallStepResult($success, Artisan::output(), $exception);
    }

    /**
     * Executes the artisan passport:install command
     * @return InstallStepResult
     */
    public function installPassport(): InstallStepResult
    {
        $success = true;
        $exception = null;

        try {
            # Run the passport:install command
            Artisan::call('passport:install --force');
        } catch (Exception $e) {
            $success = false;
            $exception = $e;
        }

        return new InstallStepResult($success, Artisan::output(), $exception);
    }

    /**
     * Executes the artisan db:seed command
     * @return InstallStepResult
     */
    public function seedDatabase(): InstallStepResult
    {
        $success = true;
        $exception = null;

        try {
            # Seed the database
            Artisan::call('db:seed');
        } catch (Exception $e) {
            $success = false;
            $exception = $e;
        }

        return new InstallStepResult($success, Artisan::output(), $exception);
    }

    /**
     * Finishes up the Shoutz0r installation by setting shoutzor.installed to true, and rebuilding the config cache
     * @return InstallStepResult
     */
    public function finishInstall(): InstallStepResult
    {
        $success = true;
        $exception = null;

        try {
            # Set installed to true
            config(['shoutzor.installed' => true]);

            // Set installed to true in the .env file too
            $editor = DotenvEditor::load();
            $editor->setKey('SHOUTZOR_INSTALLED', "true")->save();

            # Rebuild the config cache
            Artisan::call('config:cache');
        } catch (Exception $e) {
            $success = false;
            $exception = $e;
        }

        return new InstallStepResult($success, Artisan::output(), $exception);
    }
}