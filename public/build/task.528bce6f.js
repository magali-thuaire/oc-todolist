(self.webpackChunk=self.webpackChunk||[]).push([[930],{3776:(t,e,n)=>{"use strict";n.d(e,{Z:()=>a});var r=n(9755),o=n.n(r);n(2564);function a(t,e){var n=o()(t).data("href");return o().ajax({method:"GET",url:n}).done((function(t){o()("#"+e).html(t);var n,r=o()(".modal").attr("id");o()("#"+r).modal("show"),n=e,o()("button[data-bs-dismiss=modal]").click((function(){setTimeout((function(){o()("#"+n).empty()}),500)}))}))}},9976:(t,e,n)=>{"use strict";var r=n(9755),o=n.n(r),a=n(3776);o()(document).ready((function(){o()(".js-task-delete").on("click",(function(){(0,a.Z)(this,"task__modal")}))}))},7152:(t,e,n)=>{var r=n(7854),o=n(2104),a=n(614),s=n(8113),u=n(206),i=n(8053),c=/MSIE .\./.test(s),l=r.Function,d=function(t){return c?function(e,n){var r=i(arguments.length,1)>2,s=a(e)?e:l(e),c=r?u(arguments,2):void 0;return t(r?function(){o(s,this,c)}:s,n)}:t};t.exports={setTimeout:d(r.setTimeout),setInterval:d(r.setInterval)}},8053:(t,e,n)=>{var r=n(7854).TypeError;t.exports=function(t,e){if(t<e)throw r("Not enough arguments");return t}},6815:(t,e,n)=>{var r=n(2109),o=n(7854),a=n(7152).setInterval;r({global:!0,bind:!0,forced:o.setInterval!==a},{setInterval:a})},8417:(t,e,n)=>{var r=n(2109),o=n(7854),a=n(7152).setTimeout;r({global:!0,bind:!0,forced:o.setTimeout!==a},{setTimeout:a})},2564:(t,e,n)=>{n(6815),n(8417)}},t=>{t.O(0,[885],(()=>{return e=9976,t(t.s=e);var e}));t.O()}]);