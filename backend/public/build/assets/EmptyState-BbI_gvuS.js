import{c as l,a as o,j as n,f as i}from"./index-y1UJSKEV.js";import{u as c}from"./useQuery-D7-ncpN-.js";/**
 * @license lucide-react v0.577.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const d=[["path",{d:"M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2",key:"18mbvz"}],["path",{d:"M6.453 15h11.094",key:"3shlmq"}],["path",{d:"M8.5 2h7",key:"csnxdl"}]],P=l("flask-conical",d);/**
 * @license lucide-react v0.577.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=[["path",{d:"m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z",key:"wa1lgi"}],["path",{d:"m8.5 8.5 7 7",key:"rvfmvr"}]],j=l("pill",y);/**
 * @license lucide-react v0.577.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=[["path",{d:"M16 7h6v6",key:"box55l"}],["path",{d:"m22 7-8.5 8.5-5-5L2 17",key:"1t1m79"}]],_=l("trending-up",h);async function p(e){const{data:t}=await o.get(`/patients/${e}/profile`);return t.data}async function f(e){const{data:t}=await o.get(`/patients/${e}/stats`);return t.data}async function k(e,t=1,a=50){const{data:s}=await o.get(`/patients/${e}/notes`,{params:{page:t,per_page:a}});return s}function q(e){return c({queryKey:["patient-profile",e],queryFn:()=>p(e),enabled:e!=null&&e>0})}function v(e){return c({queryKey:["patient-stats",e],queryFn:()=>f(e),enabled:e!=null&&e>0,staleTime:6e4})}function b(e,t=1,a=50){return c({queryKey:["patient-notes",e,{page:t,perPage:a}],queryFn:()=>k(e,t,a),enabled:e!=null&&e>0})}function F({variant:e="text",width:t,height:a,className:s,count:r=1}){const u=Array.from({length:r});return n.jsx(n.Fragment,{children:u.map((x,m)=>n.jsx("div",{className:i("skeleton",e==="text"&&"skeleton-text",e==="heading"&&"skeleton-heading",e==="card"&&"skeleton-card",e==="avatar"&&"skeleton-avatar",s),style:{width:t,height:a},"aria-hidden":"true"},m))})}function S({icon:e,title:t,message:a,action:s,className:r}){return n.jsxs("div",{className:i("empty-state",r),children:[e&&n.jsx("div",{className:"empty-icon",children:e}),n.jsx("h3",{className:"empty-title",children:t}),a&&n.jsx("p",{className:"empty-message",children:a}),s]})}export{S as E,P as F,j as P,F as S,_ as T,q as a,v as b,b as u};
