import{c as x,j as e,f as n,b as h}from"./index-BhyQuVpl.js";/**
 * @license lucide-react v0.577.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=[["path",{d:"M5 12h14",key:"1ays0h"}],["path",{d:"m12 5 7 7-7 7",key:"xquz4c"}]],f=x("arrow-right",u);function p({className:l,label:d,value:m,description:r,trend:s,variant:t="default",icon:a,to:c,...o}){const i=e.jsxs("div",{className:n("metric-card",t!=="default"&&t,c&&"cursor-pointer",l),...o,children:[e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsx("span",{className:"metric-label",children:d}),a&&e.jsx("span",{className:"text-text-muted",children:a})]}),e.jsx("div",{className:"metric-value",children:m}),r&&e.jsx("div",{className:"metric-description",children:r}),s&&e.jsx("div",{className:n("metric-trend",s.direction),children:s.value})]});return c?e.jsx(h,{to:c,style:{textDecoration:"none",color:"inherit"},children:i}):i}export{f as A,p as M};
