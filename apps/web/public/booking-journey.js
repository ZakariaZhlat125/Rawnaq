(function(root){
  // Sort presentation rows only; scheduling and selected service order stay untouched.
  function groupsByTime(groups, times, selection){
    const rank=new Map(selection.map((s,i)=>[String(s.id),i]));
    const minutes=value=>{
      const match=/^(\d{1,2}):(\d{2})$/.exec(String(value||''));
      return match && +match[1]<24 && +match[2]<60 ? +match[1]*60 + +match[2] : Infinity;
    };
    const entries=groups.flatMap(group=>group.services.map(service=>({group,service})));
    entries.sort((a,b)=>{
      const x=minutes(times[a.service.id]),y=minutes(times[b.service.id]);
      return (x===y?0:x<y?-1:1) || rank.get(String(a.service.id))-rank.get(String(b.service.id));
    });
    const rows=[];
    entries.forEach(({group,service})=>{
      let row=rows[rows.length-1];
      if(!row || row.id!==group.id){
        row={...group,key:String(group.id)+'-'+String(service.id),services:[]};
        rows.push(row);
      }
      row.services.push(service);
    });
    return rows;
  }
  function schedule(services, slotsFor, startTime){
    const minutes=t=>{const m=/^(\d{1,2}):(\d{2})$/.exec(String(t||''));return m&&+m[1]<24&&+m[2]<60?+m[1]*60 + +m[2]:null};
    const format=n=>String(Math.floor(n/60)).padStart(2,'0')+':'+String(n%60).padStart(2,'0');
    let cursor=minutes(startTime);
    const remaining=[...services],rows=[];
    if(cursor===null||!remaining.length)return{complete:false,rows,end:''};
    while(remaining.length){
      let best=null;
      remaining.forEach((service,index)=>{
        const candidates=(slotsFor(service)||[]).filter(t=>minutes(t)!==null && minutes(t)>=cursor).sort((a,b)=>minutes(a)-minutes(b));
        const time=candidates[0];
        if(time && (!best || minutes(time)<minutes(best.time)))best={service,index,time};
      });
      if(!best)return{complete:false,rows,missing:remaining[0],end:rows.length?rows[rows.length-1].end:''};
      const start=minutes(best.time),end=start+Math.max(0,Number(best.service.dur)||0);
      rows.push({service:best.service,start:best.time,end:format(end),expected:format(cursor),shifted:start>cursor});
      remaining.splice(best.index,1);cursor=end;
    }
    return{complete:true,rows,end:format(cursor)};
  }
  root.SamiBookingJourney={groupsByTime,schedule};
})(globalThis);
