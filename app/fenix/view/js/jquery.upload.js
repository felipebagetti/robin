//@include "/app/fenix/view/js/Fenix.js"

/*
 *  Project: jQuery Upload
 *  Description: Upload component that creates a upload instead of a file componente when available
 */

;(function ( $, window, document, undefined ) {

    // Create the defaults once
    var defaults = {
        baseUri : ''
    };

    // The actual plugin constructor
    function Upload( element, options ) {
        this.element = element;

        // jQuery has an extend method which merges the contents of two or objects
        this.options = $.extend( {}, defaults, options );
        
        // Save the default for possible future access
        this._defaults = defaults;
        
        this.init();
    }
    
    Upload.prototype = {

        _hidden: null,
        _input: null,
        _progress: null,
        _isReady: true,
        _form: null,
        _iframe: null,
        
        _hash: null,
        
        init: function() {
            
            this._input = $(this.element);
            this._progress = $('<div class="progress" style="display: none; width: 200px;"><div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div>');
            this._hidden = $("#" + this.element.id.toString().replace(/\_\_file$/, ''), $(this.element.parentNode));
            this._form = this._input.closest('form');
            
            // Saves references to the object
            this._hidden.data('upload', this);
            this._input.data('upload', this);
            
            // Binds the event to send the file as soon as the file is changed
            this._input.bind('change', (function(upload){ return function(){ upload.upload(); } })(this) );
            
            // Inserts the progress bar besides the input file
            this._input.before( this._progress );
        },
        
        // Creates the default url to where send files
        
        url: function(){
            
            this._hash = Sha1.hash( (new Date()).getTime() * Math.random() + "" );
            
            var params = [];
            
            params.push('hash=' + this._hash);
            
            if(this.options.field.s3_bucket){
                var endpoint = "s3.amazonaws.com";
                if(this.options.field.s3_endpoint){
                    endpoint = this.options.field.s3_endpoint;
                }
                return "https://"+endpoint+"/"+this.options.field.s3_bucket+"/";
            }

            return this.options.baseUri + 'upload?' + params.join("&");
        },
        
        // Check the browser support for file uploads
        checkSupport: function(){
            var ret = true;
            
            // <= MSIE 9 is unssuported
            var match = navigator.userAgent.toString().match(/MSIE\s([0-9\.]+)/);
            if(match !== null && parseInt(match[1], 10) <= 9){
                ret = false;
            }
            
            // HTML 5 is required
            if(window.FormData === undefined){
                ret = false;
            }
            
            // Mensagem de alerta de suporte
            if(ret == false){
                var msg = '<center>Seu navegador não é suportado para o envio de arquivos.' +
                          '<br><br>Por favor, atualize para uma versão mais moderna.</center>';
                Fenix.alert(msg, 'Navegador Não Suportado');
            }
            
            return ret;
        },
        
        // Initializes the upload based on the avaiabilites of the user's browser
        upload: function(){
            
            // Process de upload
            if( this.checkSupport() ){
                this.uploadXhr();
            }
            
        },
        
        // Process the upload with XHR
        uploadXhr: function(){
            
            var upload = this;
            
            for(var i = 0; i < upload._input.get(0).files.length; i++){
                
                var url = upload.url();
                
                var file = upload._input.get(0).files[i];
                
                // File size validatation
                if(file.size > parseInt(upload.options.size,10)*1024){
                    Fenix.alert('<center>O tamanho máximo de um arquivo a ser carregado é ' + $.fn.upload.size(parseInt(upload.options.size,10)*1024) + " mas o arquivo selecionado possui " +  $.fn.upload.size(file.size) + ".</center>", 'Tamanho do Arquivo');
                    this.reset();
                    return;
                }
                
                var formData = new FormData();
                
                // Upload utilizando o Amazon S3
                if(upload.options.field.s3_bucket){
                    
                    upload._hash = upload._hash.substr(0, 10)+"/"+file.name;
                    
                    formData.append("key", upload.options.field.s3_key + upload._hash);
                    
                    formData.append("AWSAccessKeyId", upload.options.field.s3_access_key_id);
                    formData.append("bucket", upload.options.field.s3_bucket);
                    formData.append("acl", upload.options.field.s3_acl);
                    
                    formData.append("success_action_status", upload.options.field.s3_success_action_status);
                    
                    if(file.type){
                        formData.append("Content-Type", file.type);
                    } else {
                        formData.append("Content-Type", "application/octet-stream");
                    }
                    
                    formData.append("Content-Disposition", "inline; filename=\""+file.name+"\"");
                    
                    formData.append("policy", upload.options.field.s3_policy);
                    formData.append("signature", upload.options.field.s3_signature);
                    
                    formData.append("file", file, file.name);
                    
                }
                // Upload para o fenix
                else {
                    formData.append("arquivo__file", file, file.name);
                }
                
                upload._input.hide();
                upload._progress.show();
                
                $('.progress-bar', upload._progress).css('width', '0%');
                
                upload._hidden.trigger('upload-start');
                
                upload._isReady = false;
                
                $.ajax({
                    url: url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    xhr: function(){
                        var xhr = new window.XMLHttpRequest();
                        
                        xhr.upload.addEventListener("progress", function(e){
                            if (e.lengthComputable) {
                                var position = e.position || e.loaded;
                                var total = e.totalSize || e.total;
                                $('.progress-bar', upload._progress)
                                     .text(Math.round((position/total)*100)+'%')
                                     .css('width', Math.round((position/total)*100)+'%');
                            }
                        }, false);
                        
                        return xhr;
                    },
                    fail: function(){
                        upload._isReady = true;  
                    },
                    success: function(data){
                        if(upload.options.field.s3_bucket){
                            var endpoint = upload.options.field.s3_endpoint ? upload.options.field.s3_endpoint : "s3.amazonaws.com";
                            var urlDownload = "https://"+upload.options.field.s3_bucket+"."+endpoint+"/"+upload.options.field.s3_key+upload._hash;
                            if(upload.options.field.s3_bucket.indexOf(".") == -1){
			                    urlDownload = "https://"+endpoint+"/"+upload.options.field.s3_bucket+"/"+upload.options.field.s3_key+upload._hash;
			                }
                            var info = {
                                "name": file.name,
                                "size": file.size,
                                "type": file.type,
                                "hash": urlDownload
                            };
                            upload.callback(info);
                        } else {
                            eval( data.replace('<script>window.parent.', '').replace('</script>', '') );
                            upload._isReady = true;
                        }
                    }
                });
                 
            }
        },
        
        callback: function(info){
            
            // Fills the input with file upload metadata
            this._hidden.val( JSON.stringify(info) );
            
            // Hides the progress bar
            this._progress.hide();
            
            // Show the file information instead of the uploader
            this.show(info);
            
            this._isReady = true;
            
            Fenix.closeLoading();
            
            this._hidden.trigger('upload-finish');
            
            // Remove the required from inputs hidden and file
            if(this.options.required){
                this._hidden.removeClass('required').attr('data-required', null);
                $(this.element).removeClass('required').attr('data-required', null);
            }
            
        },
        
        show: function(info){
            
            $(this.element).css('display', 'none');
            
            var span = $('<span></span>');
            
            if( this.options.field.type == 'image' ){
                span.append( $('<img class="img-thumbnail" src="'+$.fn.upload.urlDownload(info.hash)+'" onclick="window.open(this.src);" style="cursor: pointer; max-height: 40px;">')  );
                span.append( '&nbsp;&nbsp;' );
            }
            
            span.append( $("<span><a href='"+$.fn.upload.urlDownload(info.hash)+"' target='_blank'>"+info.name+"</a>" + " de " + $.fn.upload.size(info.size) + " &nbsp;</span>") );
            
            var changeFile = $('<input type="button" class="btn btn-xs btn-info" value=" trocar arquivo " />');
            var that = this;
            changeFile.on('click', function(e){ 
                if( that.checkSupport() ){
	                that.reset();
	                that._input.trigger('click');
                } else {
                    e.preventDefault();
                    return false;
                }
            });
            
            span.append( changeFile );
            
            $(this.element.parentNode).append(span);
            
        },
        
        reset: function(){
            
            $(this.element).css('display', '');
            this._hidden.val('');
            this._input.val('');
            
            $('span', $(this.element.parentNode)).remove();
            
            // Put the required to inputs hidden and file
            if(this.options.required){
                this._hidden.addClass('required').attr('data-required', true);
                $(this.element).addClass('required').attr('data-required', true);
            }
            
            this._hidden.trigger('upload-reset');
        },
        
        isReady: function(){
            return this._isReady;
        }
        
    };
        
    var methods = {
        
        callback: function(info){
              return this.each(function(){
                if($.data(this, "upload") && $.data(this, "upload").callback){
	                $.data(this, "upload").callback(info);
                }
            });
        },
        
        reset: function(){
            return this.each(function(){
                $.data(this, "upload").reset();
            });
        },
        
        getUrl: function(){
            var ret = null;
            this.each(function(){
                ret = $.data(this, "upload").url();
            });
            return ret;
        },
        
        getHash: function(){
            var ret = null;
            this.each(function(){
                ret = $.data(this, "upload")._hash;
            });
            return ret;
        },
        
        getObject: function(){
            var ret = null;
            this.each(function(){
                ret = $.data(this, "upload");
            });
            return ret;
        },
        
        isReady: function(){
            var ret = null;
            this.each(function(){
                ret = $.data(this, "upload").isReady();
            });
            return ret;
        }
        
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn.upload = function ( method ) {
        
        // Method calling logic
        if ( methods[method] ) {
            
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
            
        } else if ( typeof method === 'object' || ! method ) {

            return this.each(function () {
                if (!$.data(this, "upload")) {
                    $.data(this, "upload", new Upload( this, method ));
                }
            });
            
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.upload' );
        }   
    };
    
    $.fn.upload.size = function(size){
        var u = "bytes";
        
        if(size >= 1024){
            size = Math.round((size/1024)*100)/100;
            u = "KB";
        }
        
        if(size >= 1024){
            size = Math.round((size/1024)*100)/100;
            u = "MB";
        }
        
        return size + "&nbsp;" + u;
    };
    
    $.fn.upload.urlDownload = function(hash){
        if(hash.indexOf("http") == 0){
            return hash;
        }
        return "download?" + hash;
    };
    
    $.fn.upload.callback = function(info){
        $('input[type="file"]').each(function(k, v){
            if($(v).upload('getHash') == info.hash){
                $(v).upload('callback', info);
            }
        });
    };
    
    // Default formatter for form upload
    $.fn.upload.formatterForm = function(text, record, field, form, table, tr, td, options){
        
        var hidden = $('<input type="hidden" id="'+field.name+'" name="'+field.name+'"></input>');
        td.append( hidden );
        
        var input = $('<input type="file" id="'+field.name+'__file" name="'+field.name+'__file"></input>');
        td.append( input );
        
        // Marcação de obrigatoriedade do campo
        var required = false;
        if($.type(field.required) != "undefined" && field.required == "1"){
            input.addClass('required');
            input.attr('data-required', 'true');
            required = true;
        }
        
        // Initializes the uploader
        var defaults = {
            required: required,
            size: field.size || null,
            field: field,
            baseUri: form ? form.options.baseUri : ''
        };
        options = $.extend({}, defaults, options);
        
        input.upload( options );
        
        // Load the value in the component
        if(record && record[field.name]){
            var info = null;
            
            try {
                info = JSON.parse(record[field.name]);
            } catch(e){}
            
            if(info != null){
                window.setTimeout(function(){
                    input.upload('callback', info);
                }, 100);
            }
        }
    };
    
    // Default formatter for a column of a file in a grid
    $.fn.upload.formatterGrid = function(text, record, column, grid, table, tr, td, formatter){
        
        var info = null;
        
        try {
            info = JSON.parse(text);
        } catch(e){}
        
        if(info != null){
            
            $('span', td).remove();
            
            var a = $("<span><a href='"+$.fn.upload.urlDownload(info.hash)+"' target='_blank'>"+info.name+"</a>" + " de " + $.fn.upload.size(info.size) + "</span>");
            td.append(a);
        }
        
    };

})( jQuery, window, document );
