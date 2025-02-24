(function(){
    var u={
        $find: function(selector){
            return this.querySelector(selector);
        },
        $findAll: function(selector){
            return this.querySelectorAll(selector);
        },
        $child: function(selector){
            return Array.from(this.children).filter(function(e) {
                return e.matches(selector);
            });
        },
        $on: function(event, selector, func){
            if(typeof selector==="string" && typeof func==="function"){
                this.addEventListener(event, function(e){
                    if(e.target.matches(selector)){
                        func.apply(e.target, arguments);
                    }
                });
            }
            else if(typeof selector==="function" && func===undefined){
                func=selector;
                this.addEventListener(event, function(e){
                    func.apply(e.target, arguments);
                });
            }
        }
    };
    Object.assign(Element.prototype, u);
    Object.assign(Element.prototype, {
        $attr: function(name, value){
            if(value===undefined) return this.getAttribute(name);
            return this.setAttribute(name, value);
        },
    });
    Object.assign(document, u);
    Object.assign(window, {
        $find: function(selector){
            return document.$find(selector);
        },
        $findAll: function(selector){
            return document.$findAll(selector);
        },
    });
})();
(async function(){
    document.$on('click', 'details>.close-details', function(event){
        this.parentElement.remove();
    });
    document.$on('click', 'summaryb', function(event){
        var p=this.parentElement;
        if(p.matches('details')){
            p.open=!p.open;
        }
    });
    document.$on('click', '[frame-load]', function(event){
        var el=this;
        var id='frame';//el.$attr('frame-load');
        var det=$find('#container>#'+id);
        var ifr;
        if(!det){
            det=document.createElement('div');
            det.open=true;
            det.id=id;
            det.classList.add('space');
            det.innerHTML='<summary></summary><a class="close-details"></a><iframe style="max-height: 50px;"></iframe><summaryb></summaryb>';
            $find('#container').append(det);
            ifr=det.$find('iframe');
            ifr.id='iframe_'+id;
            ifr.onload=function(event){
                var fn=function(){
                    ifr.style['max-height']='100px';
                    ifr.classList.remove('unable');
                    det.open=true;
                    det.scrollIntoView();
                    var h=(ifr.contentDocument || ifr.contentWindow.document).body.scrollHeight;
                    if(h>0){
                        ifr.style['max-height']=h+'px';
                    }
                };
                setTimeout(fn, 800);
            };
            ifr.onerror=function(event){
                ifr.style['max-height']='100px';
                ifr.classList.remove('unable');
                det.open=true;
                det.scrollIntoView();
            };
        }
        det.scrollIntoView();
        ifr=det.$find('iframe');
        if(ifr.matches('.unable')) return;
        // det.$find('summary').innerText=el.innerText;
        // det.$find('summaryb').innerText=el.innerText;
        ifr.src='';
        ifr.classList.add('unable');
        ifr.src=el.$attr('href');
    });
    document.$on('load', 'iframe', );
})();