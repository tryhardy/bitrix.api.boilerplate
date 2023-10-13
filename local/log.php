<?php
require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php');

if (!$USER->IsAdmin()) {
    die();
}

if ($_GET['clear'] === 'Y') {
    unlink(LOG_FILENAME);
}
?>
<h1>Вывод лог файла</h1>
<p><?php echo LOG_FILENAME ?></p>
<a href="?clear=N">
    <button type="submit">Обновить</button>
</a>
<form action=""
      method="get">
    <input type="hidden" name="clear" value="Y">
    <button type="submit"
            style="margin-top: 10px">Очистить лог
    </button>
</form>

<h2>Листинг</h2>
<pre>
	<?= file_get_contents(LOG_FILENAME) ?>
</pre>
