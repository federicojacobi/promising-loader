# promising-loader
Load all WordPress scripts after then `window.load` event. Script are loadead maintaining the dependencies set up in php.

All the scripts will be loaded asynchronously as fast as possible unless specific timing is set up.

# Filters

pl_script_load_normal - List of script handles to be loaded normally.

`add_filter( 'pl_script_load_normal', function( $handles = [] ) { return $handles; } );`

---

pl_script_load_defer - Mark a script handle to be 'defer'.

`add_filter( 'pl_script_load_defer', function( $deferred_scripts = [] ) { return $deferred_scripts; } );`

---

pl_script_def_async - Mark a script handle to be async.

`add_filter( 'pl_script_def_async', function( $script_def = [] ) { return $script_def; } );`

---

pl_script_load_windowload - All scripts not marked above will be loaded on window.load.

---

pl_skip_preload_css - All styles will be set to be preloaded unless added to the array this filter exposes.

`add_filter( 'pl_skip_preload_css', function( $styles = [] ) { return $styles; } );`

---

pl_preload_fonts - Array of font urls to be preloaded.

`add_filter( 'pl_preload_fonts', function( $font_urls = [] ) { return $font_urls; } );`

---

pl_preconnect_origins - Array of urls to do preconnect on.

`add_filter( 'pl_preconnect_origins', function( $preconnect_urls = [] ) { return $preconnect_urls; } );`

---

pl_preload_scripts - Experimental, preload scripts.

---
# Inner workings


# Known bugs