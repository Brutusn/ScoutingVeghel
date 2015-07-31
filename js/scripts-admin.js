// Functions on the admin side :)
function laadTabel() {

	ajax.post("/grab-tabel.php", {what: "all"}, function (msg) {
		msg = msg.replace(/^"(.*)"$/, '$1');
		msg = msg.replace("Array", "");
		msg = JSON.parse(msg);	
		
		console.info(msg);
		
		var t = "";
		for (var i = 0; i<msg.length;i++){
			t += "<b>"+msg[i].naam + "</b> " + msg[i].mail + "<br />";
		}
		
		$I("deelnemer-tabel").innerHTML = t;
	});
	
}

laadTabel();