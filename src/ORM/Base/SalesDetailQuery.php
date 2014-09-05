<?php

namespace ORM\Base;

use \Exception;
use \PDO;
use ORM\SalesDetail as ChildSalesDetail;
use ORM\SalesDetailQuery as ChildSalesDetailQuery;
use ORM\Map\SalesDetailTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'sales_detail' table.
 *
 *
 *
 * @method     ChildSalesDetailQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildSalesDetailQuery orderBySalesId($order = Criteria::ASC) Order by the sales_id column
 * @method     ChildSalesDetailQuery orderByType($order = Criteria::ASC) Order by the type column
 * @method     ChildSalesDetailQuery orderByStockId($order = Criteria::ASC) Order by the stock_id column
 * @method     ChildSalesDetailQuery orderByAmount($order = Criteria::ASC) Order by the amount column
 * @method     ChildSalesDetailQuery orderByUnitId($order = Criteria::ASC) Order by the unit_id column
 * @method     ChildSalesDetailQuery orderByUnitPrice($order = Criteria::ASC) Order by the unit_price column
 * @method     ChildSalesDetailQuery orderByDiscount($order = Criteria::ASC) Order by the discount column
 * @method     ChildSalesDetailQuery orderByTotalPrice($order = Criteria::ASC) Order by the total_price column
 * @method     ChildSalesDetailQuery orderByStockBuy($order = Criteria::ASC) Order by the stock_buy column
 * @method     ChildSalesDetailQuery orderByStockSellPublic($order = Criteria::ASC) Order by the stock_sell_public column
 * @method     ChildSalesDetailQuery orderByStockSellDistributor($order = Criteria::ASC) Order by the stock_sell_distributor column
 * @method     ChildSalesDetailQuery orderByStockSellMisc($order = Criteria::ASC) Order by the stock_sell_misc column
 * @method     ChildSalesDetailQuery orderByStockDiscount($order = Criteria::ASC) Order by the stock_discount column
 * @method     ChildSalesDetailQuery orderByStatus($order = Criteria::ASC) Order by the status column
 *
 * @method     ChildSalesDetailQuery groupById() Group by the id column
 * @method     ChildSalesDetailQuery groupBySalesId() Group by the sales_id column
 * @method     ChildSalesDetailQuery groupByType() Group by the type column
 * @method     ChildSalesDetailQuery groupByStockId() Group by the stock_id column
 * @method     ChildSalesDetailQuery groupByAmount() Group by the amount column
 * @method     ChildSalesDetailQuery groupByUnitId() Group by the unit_id column
 * @method     ChildSalesDetailQuery groupByUnitPrice() Group by the unit_price column
 * @method     ChildSalesDetailQuery groupByDiscount() Group by the discount column
 * @method     ChildSalesDetailQuery groupByTotalPrice() Group by the total_price column
 * @method     ChildSalesDetailQuery groupByStockBuy() Group by the stock_buy column
 * @method     ChildSalesDetailQuery groupByStockSellPublic() Group by the stock_sell_public column
 * @method     ChildSalesDetailQuery groupByStockSellDistributor() Group by the stock_sell_distributor column
 * @method     ChildSalesDetailQuery groupByStockSellMisc() Group by the stock_sell_misc column
 * @method     ChildSalesDetailQuery groupByStockDiscount() Group by the stock_discount column
 * @method     ChildSalesDetailQuery groupByStatus() Group by the status column
 *
 * @method     ChildSalesDetailQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildSalesDetailQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildSalesDetailQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildSalesDetailQuery leftJoinSales($relationAlias = null) Adds a LEFT JOIN clause to the query using the Sales relation
 * @method     ChildSalesDetailQuery rightJoinSales($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Sales relation
 * @method     ChildSalesDetailQuery innerJoinSales($relationAlias = null) Adds a INNER JOIN clause to the query using the Sales relation
 *
 * @method     ChildSalesDetailQuery leftJoinStock($relationAlias = null) Adds a LEFT JOIN clause to the query using the Stock relation
 * @method     ChildSalesDetailQuery rightJoinStock($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Stock relation
 * @method     ChildSalesDetailQuery innerJoinStock($relationAlias = null) Adds a INNER JOIN clause to the query using the Stock relation
 *
 * @method     ChildSalesDetailQuery leftJoinUnit($relationAlias = null) Adds a LEFT JOIN clause to the query using the Unit relation
 * @method     ChildSalesDetailQuery rightJoinUnit($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Unit relation
 * @method     ChildSalesDetailQuery innerJoinUnit($relationAlias = null) Adds a INNER JOIN clause to the query using the Unit relation
 *
 * @method     \ORM\SalesQuery|\ORM\StockQuery|\ORM\UnitQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildSalesDetail findOne(ConnectionInterface $con = null) Return the first ChildSalesDetail matching the query
 * @method     ChildSalesDetail findOneOrCreate(ConnectionInterface $con = null) Return the first ChildSalesDetail matching the query, or a new ChildSalesDetail object populated from the query conditions when no match is found
 *
 * @method     ChildSalesDetail findOneById(string $id) Return the first ChildSalesDetail filtered by the id column
 * @method     ChildSalesDetail findOneBySalesId(string $sales_id) Return the first ChildSalesDetail filtered by the sales_id column
 * @method     ChildSalesDetail findOneByType(string $type) Return the first ChildSalesDetail filtered by the type column
 * @method     ChildSalesDetail findOneByStockId(string $stock_id) Return the first ChildSalesDetail filtered by the stock_id column
 * @method     ChildSalesDetail findOneByAmount(int $amount) Return the first ChildSalesDetail filtered by the amount column
 * @method     ChildSalesDetail findOneByUnitId(string $unit_id) Return the first ChildSalesDetail filtered by the unit_id column
 * @method     ChildSalesDetail findOneByUnitPrice(int $unit_price) Return the first ChildSalesDetail filtered by the unit_price column
 * @method     ChildSalesDetail findOneByDiscount(int $discount) Return the first ChildSalesDetail filtered by the discount column
 * @method     ChildSalesDetail findOneByTotalPrice(int $total_price) Return the first ChildSalesDetail filtered by the total_price column
 * @method     ChildSalesDetail findOneByStockBuy(int $stock_buy) Return the first ChildSalesDetail filtered by the stock_buy column
 * @method     ChildSalesDetail findOneByStockSellPublic(int $stock_sell_public) Return the first ChildSalesDetail filtered by the stock_sell_public column
 * @method     ChildSalesDetail findOneByStockSellDistributor(int $stock_sell_distributor) Return the first ChildSalesDetail filtered by the stock_sell_distributor column
 * @method     ChildSalesDetail findOneByStockSellMisc(int $stock_sell_misc) Return the first ChildSalesDetail filtered by the stock_sell_misc column
 * @method     ChildSalesDetail findOneByStockDiscount(int $stock_discount) Return the first ChildSalesDetail filtered by the stock_discount column
 * @method     ChildSalesDetail findOneByStatus(string $status) Return the first ChildSalesDetail filtered by the status column
 *
 * @method     ChildSalesDetail[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildSalesDetail objects based on current ModelCriteria
 * @method     ChildSalesDetail[]|ObjectCollection findById(string $id) Return ChildSalesDetail objects filtered by the id column
 * @method     ChildSalesDetail[]|ObjectCollection findBySalesId(string $sales_id) Return ChildSalesDetail objects filtered by the sales_id column
 * @method     ChildSalesDetail[]|ObjectCollection findByType(string $type) Return ChildSalesDetail objects filtered by the type column
 * @method     ChildSalesDetail[]|ObjectCollection findByStockId(string $stock_id) Return ChildSalesDetail objects filtered by the stock_id column
 * @method     ChildSalesDetail[]|ObjectCollection findByAmount(int $amount) Return ChildSalesDetail objects filtered by the amount column
 * @method     ChildSalesDetail[]|ObjectCollection findByUnitId(string $unit_id) Return ChildSalesDetail objects filtered by the unit_id column
 * @method     ChildSalesDetail[]|ObjectCollection findByUnitPrice(int $unit_price) Return ChildSalesDetail objects filtered by the unit_price column
 * @method     ChildSalesDetail[]|ObjectCollection findByDiscount(int $discount) Return ChildSalesDetail objects filtered by the discount column
 * @method     ChildSalesDetail[]|ObjectCollection findByTotalPrice(int $total_price) Return ChildSalesDetail objects filtered by the total_price column
 * @method     ChildSalesDetail[]|ObjectCollection findByStockBuy(int $stock_buy) Return ChildSalesDetail objects filtered by the stock_buy column
 * @method     ChildSalesDetail[]|ObjectCollection findByStockSellPublic(int $stock_sell_public) Return ChildSalesDetail objects filtered by the stock_sell_public column
 * @method     ChildSalesDetail[]|ObjectCollection findByStockSellDistributor(int $stock_sell_distributor) Return ChildSalesDetail objects filtered by the stock_sell_distributor column
 * @method     ChildSalesDetail[]|ObjectCollection findByStockSellMisc(int $stock_sell_misc) Return ChildSalesDetail objects filtered by the stock_sell_misc column
 * @method     ChildSalesDetail[]|ObjectCollection findByStockDiscount(int $stock_discount) Return ChildSalesDetail objects filtered by the stock_discount column
 * @method     ChildSalesDetail[]|ObjectCollection findByStatus(string $status) Return ChildSalesDetail objects filtered by the status column
 * @method     ChildSalesDetail[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class SalesDetailQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \ORM\Base\SalesDetailQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'pos', $modelName = '\\ORM\\SalesDetail', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildSalesDetailQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildSalesDetailQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildSalesDetailQuery) {
            return $criteria;
        }
        $query = new ChildSalesDetailQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildSalesDetail|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = SalesDetailTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(SalesDetailTableMap::DATABASE_NAME);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildSalesDetail A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT ID, SALES_ID, TYPE, STOCK_ID, AMOUNT, UNIT_ID, UNIT_PRICE, DISCOUNT, TOTAL_PRICE, STOCK_BUY, STOCK_SELL_PUBLIC, STOCK_SELL_DISTRIBUTOR, STOCK_SELL_MISC, STOCK_DISCOUNT, STATUS FROM sales_detail WHERE ID = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildSalesDetail $obj */
            $obj = new ChildSalesDetail();
            $obj->hydrate($row);
            SalesDetailTableMap::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildSalesDetail|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, ConnectionInterface $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(SalesDetailTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(SalesDetailTableMap::COL_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the sales_id column
     *
     * Example usage:
     * <code>
     * $query->filterBySalesId(1234); // WHERE sales_id = 1234
     * $query->filterBySalesId(array(12, 34)); // WHERE sales_id IN (12, 34)
     * $query->filterBySalesId(array('min' => 12)); // WHERE sales_id > 12
     * </code>
     *
     * @see       filterBySales()
     *
     * @param     mixed $salesId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterBySalesId($salesId = null, $comparison = null)
    {
        if (is_array($salesId)) {
            $useMinMax = false;
            if (isset($salesId['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_SALES_ID, $salesId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($salesId['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_SALES_ID, $salesId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_SALES_ID, $salesId, $comparison);
    }

    /**
     * Filter the query on the type column
     *
     * Example usage:
     * <code>
     * $query->filterByType('fooValue');   // WHERE type = 'fooValue'
     * $query->filterByType('%fooValue%'); // WHERE type LIKE '%fooValue%'
     * </code>
     *
     * @param     string $type The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByType($type = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($type)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $type)) {
                $type = str_replace('*', '%', $type);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_TYPE, $type, $comparison);
    }

    /**
     * Filter the query on the stock_id column
     *
     * Example usage:
     * <code>
     * $query->filterByStockId(1234); // WHERE stock_id = 1234
     * $query->filterByStockId(array(12, 34)); // WHERE stock_id IN (12, 34)
     * $query->filterByStockId(array('min' => 12)); // WHERE stock_id > 12
     * </code>
     *
     * @see       filterByStock()
     *
     * @param     mixed $stockId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStockId($stockId = null, $comparison = null)
    {
        if (is_array($stockId)) {
            $useMinMax = false;
            if (isset($stockId['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_ID, $stockId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($stockId['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_ID, $stockId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_ID, $stockId, $comparison);
    }

    /**
     * Filter the query on the amount column
     *
     * Example usage:
     * <code>
     * $query->filterByAmount(1234); // WHERE amount = 1234
     * $query->filterByAmount(array(12, 34)); // WHERE amount IN (12, 34)
     * $query->filterByAmount(array('min' => 12)); // WHERE amount > 12
     * </code>
     *
     * @param     mixed $amount The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByAmount($amount = null, $comparison = null)
    {
        if (is_array($amount)) {
            $useMinMax = false;
            if (isset($amount['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_AMOUNT, $amount['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($amount['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_AMOUNT, $amount['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_AMOUNT, $amount, $comparison);
    }

    /**
     * Filter the query on the unit_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUnitId(1234); // WHERE unit_id = 1234
     * $query->filterByUnitId(array(12, 34)); // WHERE unit_id IN (12, 34)
     * $query->filterByUnitId(array('min' => 12)); // WHERE unit_id > 12
     * </code>
     *
     * @see       filterByUnit()
     *
     * @param     mixed $unitId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByUnitId($unitId = null, $comparison = null)
    {
        if (is_array($unitId)) {
            $useMinMax = false;
            if (isset($unitId['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_UNIT_ID, $unitId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($unitId['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_UNIT_ID, $unitId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_UNIT_ID, $unitId, $comparison);
    }

    /**
     * Filter the query on the unit_price column
     *
     * Example usage:
     * <code>
     * $query->filterByUnitPrice(1234); // WHERE unit_price = 1234
     * $query->filterByUnitPrice(array(12, 34)); // WHERE unit_price IN (12, 34)
     * $query->filterByUnitPrice(array('min' => 12)); // WHERE unit_price > 12
     * </code>
     *
     * @param     mixed $unitPrice The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByUnitPrice($unitPrice = null, $comparison = null)
    {
        if (is_array($unitPrice)) {
            $useMinMax = false;
            if (isset($unitPrice['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_UNIT_PRICE, $unitPrice['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($unitPrice['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_UNIT_PRICE, $unitPrice['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_UNIT_PRICE, $unitPrice, $comparison);
    }

    /**
     * Filter the query on the discount column
     *
     * Example usage:
     * <code>
     * $query->filterByDiscount(1234); // WHERE discount = 1234
     * $query->filterByDiscount(array(12, 34)); // WHERE discount IN (12, 34)
     * $query->filterByDiscount(array('min' => 12)); // WHERE discount > 12
     * </code>
     *
     * @param     mixed $discount The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByDiscount($discount = null, $comparison = null)
    {
        if (is_array($discount)) {
            $useMinMax = false;
            if (isset($discount['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_DISCOUNT, $discount['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($discount['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_DISCOUNT, $discount['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_DISCOUNT, $discount, $comparison);
    }

    /**
     * Filter the query on the total_price column
     *
     * Example usage:
     * <code>
     * $query->filterByTotalPrice(1234); // WHERE total_price = 1234
     * $query->filterByTotalPrice(array(12, 34)); // WHERE total_price IN (12, 34)
     * $query->filterByTotalPrice(array('min' => 12)); // WHERE total_price > 12
     * </code>
     *
     * @param     mixed $totalPrice The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByTotalPrice($totalPrice = null, $comparison = null)
    {
        if (is_array($totalPrice)) {
            $useMinMax = false;
            if (isset($totalPrice['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_TOTAL_PRICE, $totalPrice['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($totalPrice['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_TOTAL_PRICE, $totalPrice['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_TOTAL_PRICE, $totalPrice, $comparison);
    }

    /**
     * Filter the query on the stock_buy column
     *
     * Example usage:
     * <code>
     * $query->filterByStockBuy(1234); // WHERE stock_buy = 1234
     * $query->filterByStockBuy(array(12, 34)); // WHERE stock_buy IN (12, 34)
     * $query->filterByStockBuy(array('min' => 12)); // WHERE stock_buy > 12
     * </code>
     *
     * @param     mixed $stockBuy The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStockBuy($stockBuy = null, $comparison = null)
    {
        if (is_array($stockBuy)) {
            $useMinMax = false;
            if (isset($stockBuy['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_BUY, $stockBuy['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($stockBuy['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_BUY, $stockBuy['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_BUY, $stockBuy, $comparison);
    }

    /**
     * Filter the query on the stock_sell_public column
     *
     * Example usage:
     * <code>
     * $query->filterByStockSellPublic(1234); // WHERE stock_sell_public = 1234
     * $query->filterByStockSellPublic(array(12, 34)); // WHERE stock_sell_public IN (12, 34)
     * $query->filterByStockSellPublic(array('min' => 12)); // WHERE stock_sell_public > 12
     * </code>
     *
     * @param     mixed $stockSellPublic The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStockSellPublic($stockSellPublic = null, $comparison = null)
    {
        if (is_array($stockSellPublic)) {
            $useMinMax = false;
            if (isset($stockSellPublic['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_PUBLIC, $stockSellPublic['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($stockSellPublic['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_PUBLIC, $stockSellPublic['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_PUBLIC, $stockSellPublic, $comparison);
    }

    /**
     * Filter the query on the stock_sell_distributor column
     *
     * Example usage:
     * <code>
     * $query->filterByStockSellDistributor(1234); // WHERE stock_sell_distributor = 1234
     * $query->filterByStockSellDistributor(array(12, 34)); // WHERE stock_sell_distributor IN (12, 34)
     * $query->filterByStockSellDistributor(array('min' => 12)); // WHERE stock_sell_distributor > 12
     * </code>
     *
     * @param     mixed $stockSellDistributor The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStockSellDistributor($stockSellDistributor = null, $comparison = null)
    {
        if (is_array($stockSellDistributor)) {
            $useMinMax = false;
            if (isset($stockSellDistributor['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_DISTRIBUTOR, $stockSellDistributor['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($stockSellDistributor['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_DISTRIBUTOR, $stockSellDistributor['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_DISTRIBUTOR, $stockSellDistributor, $comparison);
    }

    /**
     * Filter the query on the stock_sell_misc column
     *
     * Example usage:
     * <code>
     * $query->filterByStockSellMisc(1234); // WHERE stock_sell_misc = 1234
     * $query->filterByStockSellMisc(array(12, 34)); // WHERE stock_sell_misc IN (12, 34)
     * $query->filterByStockSellMisc(array('min' => 12)); // WHERE stock_sell_misc > 12
     * </code>
     *
     * @param     mixed $stockSellMisc The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStockSellMisc($stockSellMisc = null, $comparison = null)
    {
        if (is_array($stockSellMisc)) {
            $useMinMax = false;
            if (isset($stockSellMisc['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_MISC, $stockSellMisc['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($stockSellMisc['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_MISC, $stockSellMisc['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_SELL_MISC, $stockSellMisc, $comparison);
    }

    /**
     * Filter the query on the stock_discount column
     *
     * Example usage:
     * <code>
     * $query->filterByStockDiscount(1234); // WHERE stock_discount = 1234
     * $query->filterByStockDiscount(array(12, 34)); // WHERE stock_discount IN (12, 34)
     * $query->filterByStockDiscount(array('min' => 12)); // WHERE stock_discount > 12
     * </code>
     *
     * @param     mixed $stockDiscount The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStockDiscount($stockDiscount = null, $comparison = null)
    {
        if (is_array($stockDiscount)) {
            $useMinMax = false;
            if (isset($stockDiscount['min'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_DISCOUNT, $stockDiscount['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($stockDiscount['max'])) {
                $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_DISCOUNT, $stockDiscount['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STOCK_DISCOUNT, $stockDiscount, $comparison);
    }

    /**
     * Filter the query on the status column
     *
     * Example usage:
     * <code>
     * $query->filterByStatus('fooValue');   // WHERE status = 'fooValue'
     * $query->filterByStatus('%fooValue%'); // WHERE status LIKE '%fooValue%'
     * </code>
     *
     * @param     string $status The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStatus($status = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($status)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $status)) {
                $status = str_replace('*', '%', $status);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(SalesDetailTableMap::COL_STATUS, $status, $comparison);
    }

    /**
     * Filter the query by a related \ORM\Sales object
     *
     * @param \ORM\Sales|ObjectCollection $sales The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterBySales($sales, $comparison = null)
    {
        if ($sales instanceof \ORM\Sales) {
            return $this
                ->addUsingAlias(SalesDetailTableMap::COL_SALES_ID, $sales->getId(), $comparison);
        } elseif ($sales instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(SalesDetailTableMap::COL_SALES_ID, $sales->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterBySales() only accepts arguments of type \ORM\Sales or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Sales relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function joinSales($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Sales');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Sales');
        }

        return $this;
    }

    /**
     * Use the Sales relation Sales object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \ORM\SalesQuery A secondary query class using the current class as primary query
     */
    public function useSalesQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinSales($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Sales', '\ORM\SalesQuery');
    }

    /**
     * Filter the query by a related \ORM\Stock object
     *
     * @param \ORM\Stock|ObjectCollection $stock The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByStock($stock, $comparison = null)
    {
        if ($stock instanceof \ORM\Stock) {
            return $this
                ->addUsingAlias(SalesDetailTableMap::COL_STOCK_ID, $stock->getId(), $comparison);
        } elseif ($stock instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(SalesDetailTableMap::COL_STOCK_ID, $stock->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByStock() only accepts arguments of type \ORM\Stock or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Stock relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function joinStock($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Stock');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Stock');
        }

        return $this;
    }

    /**
     * Use the Stock relation Stock object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \ORM\StockQuery A secondary query class using the current class as primary query
     */
    public function useStockQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinStock($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Stock', '\ORM\StockQuery');
    }

    /**
     * Filter the query by a related \ORM\Unit object
     *
     * @param \ORM\Unit|ObjectCollection $unit The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildSalesDetailQuery The current query, for fluid interface
     */
    public function filterByUnit($unit, $comparison = null)
    {
        if ($unit instanceof \ORM\Unit) {
            return $this
                ->addUsingAlias(SalesDetailTableMap::COL_UNIT_ID, $unit->getId(), $comparison);
        } elseif ($unit instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(SalesDetailTableMap::COL_UNIT_ID, $unit->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByUnit() only accepts arguments of type \ORM\Unit or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Unit relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function joinUnit($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Unit');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Unit');
        }

        return $this;
    }

    /**
     * Use the Unit relation Unit object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \ORM\UnitQuery A secondary query class using the current class as primary query
     */
    public function useUnitQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinUnit($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Unit', '\ORM\UnitQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildSalesDetail $salesDetail Object to remove from the list of results
     *
     * @return $this|ChildSalesDetailQuery The current query, for fluid interface
     */
    public function prune($salesDetail = null)
    {
        if ($salesDetail) {
            $this->addUsingAlias(SalesDetailTableMap::COL_ID, $salesDetail->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the sales_detail table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SalesDetailTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            SalesDetailTableMap::clearInstancePool();
            SalesDetailTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    /**
     * Performs a DELETE on the database based on the current ModelCriteria
     *
     * @param ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public function delete(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(SalesDetailTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(SalesDetailTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            SalesDetailTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            SalesDetailTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // SalesDetailQuery
