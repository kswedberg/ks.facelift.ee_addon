<?php
if($OUTPUT_CACHE_DATA) {
	$cache_file = get_cache_fn(md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), 'txt');

	if(file_exists($cache_file))
		$cache_data = unserialize(file_get_contents($cache_file));
	else
		$cache_data = array();
		
	if(!isset($_GET['_supercache_refresh'])) {
?>
<script language="javascript" type="application/javascript">
var FLIRSuperCache = {
	init: function(){
		FLIR.elURLCache = <?php echo json_encode($cache_data); ?>;
		FLIRStyle.prototype.origURL = FLIRStyle.prototype.URL;
		FLIRStyle.prototype.URL = function(o, text) {
			var id = o.flirId.split('-')[1];
//			console.log('Supercache URL firing');
			if(typeof FLIR.elURLCache == 'undefined' || typeof FLIR.elURLCache[id] == 'undefined') {
//				console.info('Generating URL');
				return this.origURL(o, text)+'&i='+id;
			}else {
//				console.info('URL cached');
				return FLIR.elURLCache[id]['F'];
			}
		};
	} 
};
FLIR.install(FLIRSuperCache);
</script>
<?php
	} // if !isset($_GET['_supercache_refresh'])
}elseif(preg_match('#^\d+$#', $_GET['i'])) { // if($OUTPUT_CACHE_DATA) {
	$cache_file = get_cache_fn(md5(substr(strstr($_SERVER['HTTP_REFERER'], '://'), 3)), 'txt');
	$cache_data = unserialize(file_get_contents($cache_file));
	$cache_data[$_GET['i']] = array('F' => $FLIR['cache'], 'M' => $FLIR['file_meta']);
	file_put_contents($cache_file, serialize($cache_data));
} // if($OUTPUT_CACHE_DATA) {
?>