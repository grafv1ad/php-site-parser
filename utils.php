<?php
    function show($data): void {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    function getRoot(): string {
        return preg_replace('/\/\?.*/', '', $_SERVER['REQUEST_URI']);
    }
?>