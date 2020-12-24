<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class SyncOrderFEE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:order_fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $orders = PurchaseOrder::whereIn('order_number', self::ARR)
                ->where('status', '!=', PurchaseOrder::STATUS_SUCCESS)
                ->get();

        $flag = 0;
        foreach ($orders as $order) {
            $now = $order->purchase_order_service_fee;
            $handle = number_format($order->totalBill() / 100 * 1, 2);

            if ($now != $handle) {
                echo "$order->order_number \n";
                $pass = number_format($order->sumQtyRealityMoney() / 100 * 1, 2);
                $order->purchase_order_service_fee = $pass;
                $order->save();

                $flag++;
                // dd($order->order_number . "-".$now."-".$handle);   
            }
            // dd($order);
        }

        dd($flag);
    }

    const ARR = [
        "MH-A1152"
        ,"MH-A1249"
        ,"MH-A1314"
        ,"MH-A1353"
        ,"MH-A1375"
        ,"MH-A1378"
        ,"MH-A1395"
        ,"MH-A1402"
        ,"MH-A1404"
        ,"MH-A1405"
        ,"MH-A1406"
        ,"MH-A1414"
        ,"MH-A1421"
        ,"MH-A1430"
        ,"MH-A1433"
        ,"MH-A1462"
        ,"MH-A1463"
        ,"MH-A1468"
        ,"MH-A1472"
        ,"MH-A1477"
        ,"MH-A1492"
        ,"MH-A1493"
        ,"MH-A1494"
        ,"MH-A1495"
        ,"MH-A1514"
        ,"MH-A1519"
        ,"MH-A1521"
        ,"MH-A1523"
        ,"MH-A1525"
        ,"MH-A1526"
        ,"MH-A1527"
        ,"MH-A1528"
        ,"MH-A1529"
        ,"MH-A1534"
        ,"MH-A1538"
        ,"MH-A1539"
        ,"MH-A1543"
        ,"MH-A1545"
        ,"MH-A1555"
        ,"MH-A1557"
        ,"MH-A1559"
        ,"MH-A1560"
        ,"MH-A1563"
        ,"MH-A1568"
        ,"MH-A1570"
        ,"MH-A1571"
        ,"MH-A1574"
        ,"MH-A1575"
        ,"MH-A1579"
        ,"MH-A1602"
        ,"MH-A1612"
        ,"MH-A1621"
        ,"MH-A1624"
        ,"MH-A1626"
        ,"MH-A1633"
        ,"MH-A1635"
        ,"MH-A1637"
        ,"MH-A1639"
        ,"MH-A1641"
        ,"MH-A1645"
        ,"MH-A1649"
        ,"MH-A1651"
        ,"MH-A1654"
        ,"MH-A1661"
        ,"MH-A1663"
        ,"MH-A1665"
        ,"MH-A1667"
        ,"MH-A1669"
        ,"MH-A1674"
        ,"MH-A1677"
        ,"MH-A1683"
        ,"MH-A1689"
        ,"MH-A1690"
        ,"MH-A1691"
        ,"MH-A1692"
        ,"MH-A1693"
        ,"MH-A1695"
        ,"MH-A1700"
        ,"MH-A1701"
        ,"MH-A1709"
        ,"MH-A1710"
        ,"MH-A1711"
        ,"MH-A1712"
        ,"MH-A1713"
        ,"MH-A1714"
        ,"MH-A1715"
        ,"MH-A1716"
        ,"MH-A1717"
        ,"MH-A1721"
        ,"MH-A1722"
        ,"MH-A1726"
        ,"MH-A1729"
        ,"MH-A1732"
        ,"MH-A1734"
        ,"MH-A1738"
        ,"MH-A1740"
        ,"MH-A1741"
        ,"MH-A1742"
        ,"MH-A1745"
        ,"MH-A1750"
        ,"MH-A1753"
        ,"MH-A1756"
        ,"MH-A1761"
        ,"MH-A1770"
        ,"MH-A1772"
        ,"MH-A1779"
        ,"MH-A1780"
        ,"MH-A1781"
        ,"MH-A1782"
        ,"MH-A1783"
        ,"MH-A1784"
        ,"MH-A1785"
        ,"MH-A1786"
        ,"MH-A1787"
        ,"MH-A1804"
        ,"MH-A1820"
        ,"MH-A1822"
        ,"MH-A1823"
        ,"MH-A1824"
        ,"MH-A1832"
        ,"MH-A1833"
        ,"MH-A1836"
        ,"MH-A1840"
        ,"MH-A1842"
        ,"MH-A1843"
        ,"MH-A1844"
        ,"MH-A1845"
        ,"MH-A1846"
        ,"MH-A1847"
        ,"MH-A1848"
        ,"MH-A1849"
        ,"MH-A1850"
        ,"MH-A1851"
        ,"MH-A1852"
        ,"MH-A1854"
    ];
}
