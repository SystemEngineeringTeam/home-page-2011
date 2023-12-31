document.getElementsByClassName = function (needle)
{
  var         my_array = document.getElementsByTagName("*");
  var         retvalue = new Array();
  var        i;
  var        j;

  for (i = 0, j = 0; i < my_array.length; i++)
  {
    var c = " " + my_array[i].className + " ";
    if (c.indexOf(" " + needle + " ") != -1)
      retvalue[j++] = my_array[i];
  }
  return retvalue;
}

function addEvent(obj, evType, fn)
{
	if (obj.addEventListener)
	{
		obj.addEventListener(evType, fn, true);
		return true;
	} 
	else if (obj.attachEvent)
	{
		var r = obj.attachEvent("on"+evType, fn);
		return r;
	} 
	else 
	{
		return false;
	}
}

addEvent(window, 'load', function()
{
	var resizables = document.getElementsByClassName('resizable');
	var i, ta, wrapper, bigger, smaller, biggerSpan, smallerSpan;
	for (i=0; i<resizables.length; i++)
	{
		ta = resizables[i];
		
		wrapper = document.createElement('DIV');
		wrapper.className = 'resizerWrapper';
		ta.parentNode.replaceChild(wrapper, ta);
		wrapper.appendChild(ta);
		
		bigger = document.createElement('A');
		bigger.className = 'resizeBigger';
		bigger.href = '#';
		
		smaller = document.createElement('A');
		smaller.className = 'resizeSmaller';
		smaller.href='#';
		
		smallerSpan = document.createElement('SPAN');
		smallerSpan.appendChild(document.createTextNode(' [△] '));
		
		biggerSpan = document.createElement('SPAN');
		biggerSpan.appendChild(document.createTextNode(' [▽] '));
		
		bigger.appendChild(biggerSpan);
		smaller.appendChild(smallerSpan);	
		
		bigger.resizer = ta;
		smaller.resizer = ta;
		
		bigger.onclick = function()
		{
			if (this.resizer.style.height == '')
				this.resizer.style.height = this.resizer.clientHeight+'px';
			this.resizer.style.height = (parseInt(this.resizer.style.height) + 30) + "px";				
			return false;	
		}
		
		smaller.onclick = function()
		{
			if (this.resizer.style.height == '')
				this.resizer.style.height = this.resizer.clientHeight+'px';
			
			if ((parseInt(this.resizer.style.height) - 30) > 30)			
				this.resizer.style.height = (parseInt(this.resizer.style.height) - 30) + "px";
			return false;	
		}		
		
		wrapper.appendChild(smaller);
		wrapper.appendChild(bigger);
	}	
});
