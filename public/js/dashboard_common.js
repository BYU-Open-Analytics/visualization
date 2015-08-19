function sendStatement(params) {
	params.timestamp = new Date().toISOString();
	// TODO absolute url ref fix
	$.post("../xapi", params);
}

// This function from http://www.ostyn.com/standards/scorm/samples/ISOTimeForSCORM.htm
/* This reusable script is copyrighted.
   Copyright (c) 2004,2005,2006 Claude Ostyn
This script is free for use with attribution
under the Creative Commons Attribution-ShareAlike 2.5 License.
To view a copy of this license, visit
http://creativecommons.org/licenses/by-sa/2.5/
or send a letter to
Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
For any other use, contact Claude Ostyn via tools@Ostyn.com.
USE AT YOUR OWN RISK!
THIS SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHOR OR COPYRIGHT HOLDER
BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

function centisecsToISODuration(n, bPrecise)
{
  // Note: SCORM and IEEE 1484.11.1 require centisec precision
  // Parameters:
  // n = number of centiseconds
  // bPrecise = optional parameter; if true, duration will
  // be expressed without using year and/or month fields.
  // If bPrecise is not true, and the duration is long,
  // months are calculated by approximation based on average number
  // of days over 4 years (365*4+1), not counting the extra days
  // for leap years. If a reference date was available,
  // the calculation could be more precise, but becomes complex,
  // since the exact result depends on where the reference date
  // falls within the period (e.g. beginning, end or ???)
  // 1 year ~ (365*4+1)/4*60*60*24*100 = 3155760000 centiseconds
  // 1 month ~ (365*4+1)/48*60*60*24*100 = 262980000 centiseconds
  // 1 day = 8640000 centiseconds
  // 1 hour = 360000 centiseconds
  // 1 minute = 6000 centiseconds
  var str = "P";
  var nCs=n;
  var nY=0, nM=0, nD=0, nH=0, nMin=0, nS=0;
  n = Math.max(n,0); // there is no such thing as a negative duration
  var nCs = n;
  // Next set of operations uses whole seconds
  //with (Math)
  //{
    nCs = Math.round(nCs);
    if (bPrecise == true)
    {
      nD = Math.floor(nCs / 8640000);
    }
    else
    {
      nY = Math.floor(nCs / 3155760000);
      nCs -= nY * 3155760000;
      nM = Math.floor(nCs / 262980000);
      nCs -= nM * 262980000;
      nD = Math.floor(nCs / 8640000);
    }
    nCs -= nD * 8640000;
    nH = Math.floor(nCs / 360000);
    nCs -= nH * 360000;
    var nMin = Math.floor(nCs /6000);
    nCs -= nMin * 6000
  //}
  // Now we can construct string
  if (nY > 0) str += nY + "Y";
  if (nM > 0) str += nM + "M";
  if (nD > 0) str += nD + "D";
  if ((nH > 0) || (nMin > 0) || (nCs > 0))
  {
    str += "T";
    if (nH > 0) str += nH + "H";
    if (nMin > 0) str += nMin + "M";
    if (nCs > 0) str += (nCs / 100) + "S";
  }
  if (str == "P") str = "PT0H0M0S";
  // technically PT0S should do but SCORM test suite assumes longer form.
  return str;
}
