/* 
jquery.animation.easing.js: Jamie Lemon, Lemon Sanver - Easing animation functions for jQuery, Jamie Lemon 2009 lemonsanver.com
Robert Penner's original easing equations modified for JQuery animate method

Below are easing equations based on Robert Penner's work, modified for JQuery
The "In" part of an animation is the start of it, the "Out" part is the end of it
If you apply "easing" at the "In" or the "Out" then the supplied animation curve is most apparent at that point
Enjoy the animation curves!

usage: $(".myImageID").animate({"left": "+=100"},{queue:false, duration:500, easing:"bounceEaseOut", complete:func});

function list:

backEaseIn
backEaseOut
backEaseInOut
bounceEaseIn
bounceEaseOut
bounceEaseInOut
circEaseIn
circEaseOut
circEaseInOut
cubicEaseIn
cubicEaseOut
cubicEaseInOut
elasticEaseIn
elasticEaseOut
expoEaseIn
expoEaseOut
expoEaseInOut
linear
quadEaseIn
quadEaseOut
quadEaseInOut
quartEaseIn
quartEaseOut
quartEaseInOut
quintEaseIn
quintEaseOut
quintEaseInOut
sineEaseIn
sineEaseOut
sineEaseInOut


Note in JQuey's native animate function the supplied parameters are supplied as follows:

easingAlgorythmEaseType: function( p, n, firstNum, diff )

@param p The time phase between 0 and 1
@param n Not sure what this is :), in any case its not used
@param firstNum The first number in the transform
@param diff The difference in in pixels required

*/

/*
Disclaimer for Robert Penner's Easing Equations license:

TERMS OF USE - EASING EQUATIONS

Open source under the BSD License.

Copyright © 2001 Robert Penner
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
    * Neither the name of the author nor the names of contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

jQuery.extend({
    
    easing: 
    {

        // ******* back
        backEaseIn:function(p, n, firstNum, diff) {

            var c=firstNum+diff;
            
            var s = 1.70158; // default overshoot value, can be adjusted to suit
            return c*(p/=1)*p*((s+1)*p - s) + firstNum;
        },
        
          
    }
});
