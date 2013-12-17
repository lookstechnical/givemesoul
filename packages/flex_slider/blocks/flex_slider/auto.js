var flexgalleryBlock = {
	
	init:function(){},	
	
	chooseImg:function(){ 
		ccm_launchFileManager('&fType=' + ccmi18n_filemanager.FTYPE_IMAGE);
	},
	
	showImages:function(){
		$("#flexgalleryBlock-imgRows").show();
		$("#flexgalleryBlock-chooseImg").show();
		$("#flexgalleryBlock-fsRow").hide();
	},

	showFileSet:function(){
		$("#flexgalleryBlock-imgRows").hide();
		$("#flexgalleryBlock-chooseImg").hide();
		$("#flexgalleryBlock-fsRow").show();
	},

	selectObj:function(obj){
		if (obj.fsID != undefined) {
			$("#flexgalleryBlock-fsRow input[name=fsID]").attr("value", obj.fsID);
			$("#flexgalleryBlock-fsRow input[name=fsName]").attr("value", obj.fsName);
			$("#flexgalleryBlock-fsRow .flexgalleryBlock-fsName").text(obj.fsName);
		} else {
			this.addNewImage(obj.fID, obj.thumbnailLevel1, obj.height, obj.title);
		}
	},

	addImages:0, 
	addNewImage: function(fID, thumbPath, imgHeight, title, description) { 
		this.addImages--; //negative counter - so it doesn't compete with real GalleryImgIds
		var GalleryImgId=this.addImages;
		var templateHTML=$('#imgRowTemplateWrap .flexgalleryBlock-imgRow').html().replace(/tempFID/g,fID);
		templateHTML=templateHTML.replace(/tempThumbPath/g,thumbPath);
		templateHTML=templateHTML.replace(/tempFilename/g,title);
		templateHTML=templateHTML.replace(/tempGalleryImgId/g,GalleryImgId).replace(/tempHeight/g,imgHeight);
		var imgRow = document.createElement("div");
		imgRow.innerHTML=templateHTML;
		imgRow.id='flexgalleryBlock-imgRow'+parseInt(GalleryImgId);	
		imgRow.className='flexgalleryBlock-imgRow';
		document.getElementById('flexgalleryBlock-imgRows').appendChild(imgRow);
		var bgRow=$('#flexgalleryBlock-imgRow'+parseInt(fID)+' .backgroundRow');
		bgRow.css('background','url('+thumbPath+') no-repeat left top');
	},
	
	removeImage: function(fID){
		$('#flexgalleryBlock-imgRow'+fID).remove();
	},
	
	moveUp:function(fID){
		var thisImg=$('#flexgalleryBlock-imgRow'+fID);
		var qIDs=this.serialize();
		var previousQID=0;
		for(var i=0;i<qIDs.length;i++){
			if(qIDs[i]==fID){
				if(previousQID==0) break; 
				thisImg.after($('#flexgalleryBlock-imgRow'+previousQID));
				break;
			}
			previousQID=qIDs[i];
		}	 
	},
	moveDown:function(fID){
		var thisImg=$('#flexgalleryBlock-imgRow'+fID);
		var qIDs=this.serialize();
		var thisQIDfound=0;
		for(var i=0;i<qIDs.length;i++){
			if(qIDs[i]==fID){
				thisQIDfound=1;
				continue;
			}
			if(thisQIDfound){
				$('#flexgalleryBlock-imgRow'+qIDs[i]).after(thisImg);
				break;
			}
		} 
	},
	serialize:function(){
		var t = document.getElementById("flexgalleryBlock-imgRows");
		var qIDs=[];
		for(var i=0;i<t.childNodes.length;i++){ 
			if( t.childNodes[i].className && t.childNodes[i].className.indexOf('flexgalleryBlock-imgRow')>=0 ){ 
				var qID=t.childNodes[i].id.replace('flexgalleryBlock-imgRow','');
				qIDs.push(qID);
			}
		}
		return qIDs;
	},	

	validate:function(){
		var failed=0; 
		
		if ($("#newImg select[name=type]").val() == 'FILESET')
		{
			if ($("#flexgalleryBlock-fsRow input[name=fsID]").val() <= 0) {
				alert(ccm_t('choose-fileset'));
				$('#flexgalleryBlock-AddImg').focus();
				failed=1;
			}	
		} else {
			qIDs=this.serialize();
			if( qIDs.length<2 ){
				alert(ccm_t('choose-min-2'));
				$('#flexgalleryBlock-AddImg').focus();
				failed=1;
			}	
		}
		
		if(failed){
			ccm_isBlockError=1;
			return false;
		}
		return true;
	} 
}

ccmValidateBlockForm = function() { return flexgalleryBlock.validate(); }
ccm_chooseAsset = function(obj) { flexgalleryBlock.selectObj(obj); }

$(function() {
	if ($("#newImg select[name=type]").val() == 'FILESET') {
		$("#newImg select[name=type]").val('FILESET');
		flexgalleryBlock.showFileSet();
	} else {
		$("#newImg select[name=type]").val('CUSTOM');
		flexgalleryBlock.showImages();
	}

	$("#newImg select[name=type]").change(function(){
		if (this.value == 'FILESET') {
			flexgalleryBlock.showFileSet();
		} else {
			flexgalleryBlock.showImages();
		}
	});
});


