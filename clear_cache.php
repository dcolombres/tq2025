<?php
/**
 * clear_cache.php
 * 
 * Este script simple se utiliza para forzar la limpieza de la caché de OPcache en el servidor.
 * Es útil durante el desarrollo para asegurar que los cambios en los archivos PHP
 * se reflejen inmediatamente sin tener que esperar a que la caché expire.
 */

opcache_reset();
echo "La caché de OPcache ha sido limpiada exitosamente.";
?>