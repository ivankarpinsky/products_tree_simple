<?php
$connection = new PDO('mysql:host=localhost;dbname=sk_design', 'root', '');

// формируем данные для групп товаров
$stmt = $connection->query('SELECT * FROM `groups`');
$groups = $stmt->fetchAll();
$groupsKeyID = array_combine(array_column($groups, 'id'), $groups);
$groupsChildrenKeyID = [];
foreach ($groups as $group) {
    $groupsChildrenKeyID[$group['id_parent']][] = $group;
}

// формируем данные для товаров
$stmt = $connection->query('SELECT * FROM `products`');
$products = $stmt->fetchAll();
$productsGroupedByGroupID = array_group('id_group', $products);
$productsKeyID = array_combine(array_column($products, 'id'), $products);

// искомая группа и искомые товары
$searchedGroupID = $_GET['group_id'] ?? 0;
$searchedProductIDs = [];

// рекурсивная функция для обхода дерева
function getChildren($groupID, $parentsID)
{
    global $groupsChildrenKeyID;
    global $groupsKeyID;
    global $productsGroupedByGroupID;

    global $searchedGroupID;
    global $searchedProductIDs;


    $childrenHtml = '';
    $productsCount = count($productsGroupedByGroupID[$groupID] ?? []);
    $productIDs = array_column($productsGroupedByGroupID[$groupID] ?? [], 'id');
    $includeSearchedGroupID = ($searchedGroupID == $groupID);

    // если дочерних групп нет, то выход из рекурсии
    if (!isset($groupsChildrenKeyID[$groupID])) {
        if ($groupID == $searchedGroupID) {
            $searchedProductIDs = $productIDs;
        }
        return ['html' => $childrenHtml, 'products_count' => $productsCount, 'include_searched_id' => $includeSearchedGroupID, 'product_ids' => $productIDs];
    }

    $children = [];
    $includeSearchedGroupID = false;
    $childrenHtml .= '<ul>';

    foreach ($groupsChildrenKeyID[$groupID] as $child) {
        $childData = $groupsKeyID[$child['id']];
        $childData['children'] = getChildren($child['id'], array_merge($parentsID, [$child['id']]));

        $productsCount += $childData['children']['products_count'];
        $productIDs = array_merge($productIDs, $childData['children']['product_ids']);
        $children[] = $childData;

        $childrenHtml .= '<li><a href="?group_id=' . $child['id'] . '">' . $childData['name'] . '</a> ';
        $childrenHtml .= $childData['children']['products_count'];

        // отображаем дочерние элементы группы в том случае
        // когда либо текущий элемент является искомой группой, либо когда искомая группа находится в дочерних группах
        if ($searchedGroupID == $child['id'] || $childData['children']['include_searched_id']) {
            $childrenHtml .= $childData['children']['html'];
            $includeSearchedGroupID = true;
        }

        $childrenHtml .= ' </li>';
    }
    $childrenHtml .= '</ul>';

    // все искомые товары
    if ($groupID == $searchedGroupID) {
        $searchedProductIDs = $productIDs;
    }

    return ['groups' => $children, 'html' => $childrenHtml, 'products_count' => $productsCount, 'include_searched_id' => $includeSearchedGroupID, 'product_ids' => $productIDs];
}

// вспомогательная функция
function array_group($key, $data)
{
    $result = [];

    foreach ($data as $val) {
        if (array_key_exists($key, $val)) {
            $result[$val[$key]][] = $val;
        } else {
            $result[""][] = $val;
        }
    }

    return $result;
}

// построение дерева
$groupsTree = getChildren(0, []);


// формируем html для товаров
$productsHtml = '';
$searchedProductIDs = array_unique($searchedProductIDs);
sort($searchedProductIDs);
foreach ($searchedProductIDs as $productID) {
    $productsHtml .= '<div>' . $productsKeyID[$productID]['name'] . '</div>';
}

$groupsTreeHtml = $groupsTree['html'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>sk design test</title>
    <style>
        .container {
            display: flex;
            flex-wrap: wrap;
        }

        .groups-nav {
            min-width: 350px;
            margin-right: 10px;
        }

        .products {
        }
    </style>
</head>
<body>
<div class="container">
    <div class="groups-nav">
        <a href="?">Все товары</a>
        <?php echo $groupsTreeHtml ?>
    </div>
    <div class="products">
        <?php echo $productsHtml ?>
    </div>
</div>
</body>
</html>
