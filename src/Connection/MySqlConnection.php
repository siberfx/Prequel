<?php

namespace Protoqol\Prequel\Connection;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use PDO;
use Protoqol\Prequel\Database\SequelAdapter;

class MySqlConnection extends Connection
{
    protected $connection;

    /**
     * MySqlConnection constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getPdo());
        $this->connection = new Connection($this->getPdo());
    }

    /**
     * @return MySqlConnection
     */
    public function getConnection()
    {
        return $this;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        $connection = config("prequel.database.connection");
        $host = config("prequel.database.host");
        $port = config("prequel.database.port");
        $database = config("prequel.database.database");

        $dsn =
            $connection .
            ":dbname=" .
            $database .
            ";host=" .
            $host .
            ";port=" .
            $port;
        $user = config("prequel.database.username");
        $pass = config("prequel.database.password");

        return new PDO($dsn, $user, $pass);
    }

    /**
     * Return with user permissions
     *
     * @return array
     */
    public function getGrants(): array
    {
        return (array)$this->connection->select(
            "SHOW GRANTS FOR CURRENT_USER();"
        )[0];
    }

    /**
     * @return MySqlGrammar
     */
    public function getGrammar()
    {
        return new MySqlGrammar();
    }

    /**
     * @return MySqlProcessor
     */
    public function getProcessor()
    {
        return new MySqlProcessor();
    }

    /**
     * Gets information about the server.
     *
     * @return array
     */
    public function getServerInfo(): array
    {
        $serverInfo = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_INFO);

        $explodedServerInfo = explode("  ", $serverInfo);
        $serverInfoArray = [];

        foreach ($explodedServerInfo as $attr) {
            $split = explode(": ", $attr);
            $key = strtoupper(
                str_replace(" ", "_", str_replace(":", "", $split[0]))
            );
            $serverInfoArray[$key] = $split[1];
        }

        return $serverInfoArray;
    }

    /**
     * @param string $database Database name
     * @param string $table Table name
     *
     * @return string
     */
    public function formatTableName(string $database, string $table): string
    {
        return $database . "." . $table;
    }

    /**
     * @param string $database Database name
     * @param string $table Table name
     *
     * @return array
     */
    public function getTableStructure(string $database, string $table): array
    {
        return $this->select("SHOW COLUMNS FROM `$database`.`$table`");
    }

    /**
     * @param string $database Database name
     * @param string $table Table name
     *
     * @return array
     */
    public function getTableData(string $database, string $table): array
    {
        return $this->connection->select("SELECT * FROM `$database`.`$table`");
    }

    /**
     * @param string $database
     *
     * @return array
     * @throws Exception
     */
    public function getTablesFromDB(string $database): array
    {
        $databaseQueries = new SequelAdapter(
            config("prequel.database.connection")
        );

        return $this->select($databaseQueries->showTablesFrom($database));
    }
}
