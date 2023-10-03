<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Dictum;

use df\core;

class PasswordAnalyzer implements IPasswordAnalyzer
{
    public const COMMON = 'eJxcvduS47qSpvkqWTYzVWbdnbuWIo9hfTNX/RhjRokQCYkkmAAhheLpxx3+/07mXmkr/AMEgiDOBwfw5Z+P//N//uP//b/+7//H4f/b6T8P+D+U259//a8ZYqE8K/z3v/4XxEy5OJiLPjz+e6nT1Axhu/z32pXy7JuxlvzfQ051VdM/8h+Eyx0OdETwL5MuQG+nbya//4R8V3myP29vkO+U3xx+G/00UZZgz5woXO5woCMav/1jL3k7vVE6fYP8DvnL5NsPk98o3n+Ddrtvb/D+23fKHw4/d/p1QPry/c8zZHB3voCG1OAbQvXtQOafvNPkj1/2Sd/xId+//WOS8OufU2+g/xr9Pr2lR8hbzQHm35Sgd0tTkSfIN8hvkN8hf0D+hPwFSX8sdO8n+IeEeEdo3/FZ74j29xP8w2e+I8TvDNcJ/r3BP8T8O3LRO9Lk/Q3+Ifne3+DfG/x7g3/Ide+IrPdv8A/R+/4N/iFl37/Bv2/w7xv8Q1Z9/wb/vsO/7/DvO/z7Dv++wz9klPfv8O87/PsO/1BU3n/Avx/w7wf8+wH/fsC/H/AP2e79B/z7Af9+wL+f8O8n/PsJ/37Cv5/w7yf8+wn/kIfff8K/n/DvF/z7Bf9+wb9f8O8X/PsF/37Bv1/wD+XhHblZChkk/PsN/37Dv9/w7zf8+w3/fsO/3/DvN/x7h3/v8O8d/qGyeX+Hf+/w7x3+vcM/lPr3d/Ovezt/s7J6GXMsje5x216N1rf0LTb68/b8ZmXsz/MNVLrllkpjy8VvqEFFniDfIL9Bfof8AfkT8hfkb8h3kyf4d4I/J/hzgj8n+HOCPyf4c4I/J/jD8L0hXG/w7w3+vcG/N/j3Bv/e4N8b/HuDf9/g3zf49w3+fYN/3+DfN/j3Df59g39Wyt5O+C6Rzvxrb6Bxlzsc6IhgK1hv99i3VH17humS5pZa3+yPRe43vPjbyYr4N/uGbwe5w4GOaPwD0orSt3Pocnt1i4vv/3o7l97oG+kfK+Xf//n1a+7K2NiduSurYr4f5A5/ofEvKzLff1tV8j3XZbGW6Yf9sSrox8kyww/E1I/vaP1+WKB+HOQOBzqi8U8r1D9+XcbwaCXoR7f07befb1Yx/fxmReOnv+6n1Ug/D3KHAx3RGHWWStJnner3T+VfViH9OsgdDnRE49/vaOt/9c8uX1sS/v7nu1VMv3/+kpq6ve33r/0jfluc/z7IHQ50xMbvWiQbWIMqolwcpGE/J5h2fvvHKmXUYe8HucOBjtj4f093iJ06y2x7p7Vrf7wL1J3Ob5dvO/Utc3XmyoXLHQ50RHDuH12+g9MC2Fql2527S3cG9d25T8kM57Q5kF4mLxDo0wn1lAcrdvCUg8N1p8GwD0t3gYM+TYC6w727Ok/dSM4GY7zFjZi72TAOXYQPqeLnLF/aqzgaHyHTOPLh3MGupAkPX7pL7EB9mP9ii47LJawbqRRQyj2pLtsB4cC++3JfOkufS05rmiJ+3SJSS+gBp1uywF0qPqSnmCnt6T5YBPQWz32EOEP2HdzFxbzu4cEcF4eTUyzbkXPHgPQ5dstOS3fgQA7LTm6bLvbRkq7w+xGWDQnbP5CAwVI8LAEBDjmd48X4moNV6ULx0pEs0q8bElcyg71/CEiEYYjBwbwaFsAY+2A/jnPoCfZcvM/42DgFfFSc4TxK5ZEX8iVLAMBj6HrgOnXu5Jkmy9q3LkJaEbuP+Ph7ZIm6x7uVzIkfM3XzEDOQVoXy3pG2zl298Orp3C0v0pZTOTINMubZCe++dGuiH+EmDQ98Dkj2yR+SMiA/gz8oTwT5LRw5HwzO4cCxc0IArxBSp4EykmsaAov5FCEYMwKBeKEblEOFE4kvZN5V4IP8WhYdKbG0KSiyNnvQeY01eRpNYWCIJ/p9fI6/ScJt8D6N8D5ZiZlWWgicnM7IpdPqAdtQeQrMkXGySX4ExflA8OkBqwcz2rN74ctfHsxXwSfPFH2oBbj03U4nYKZkhS24ZpSdufuMi9VU8xm5YQ4TSukcvHQb/jKOEAN+S3kdE0ORKFGjzBmFarasbx+yfL0hEaQZSgz40k2UaQCh6lAYQZJExcKwXMYOuUIQVSPyv2bvshNicEGNLHk8OJycup0OlsXxudN/7OhOU+wd5076irsp+C+f4QZEjGiEFzgNT531Mh7QliicnLqddst436396cz8uLDeVeBDSLAF8YFUWiRDHuiLJmzYzf9mpBeUG0Jx81TxZKboKL32Uo70ddH8iSAx9BpBpG2jJa34aUzgtLxm5salYOZKck+XUQNJRkqohwW16ZcGCv2JZUseaRt9FIjJEU9WCC03pwOzol7q2jnMoNwhW0szi8DAu9c2siSunnFWSwnpm6B6amQj5W5dp+BwcnpzitFxjbtTe+/KYrlKJ8cC+qe6zJEx2AxgCb20tMAZEh2BfA7Lp9FlxNuUtnDZYNgQx7lnfwBFMAfJC8AI0VH2/kvnhHKhMNHhFZLVnGR+ds6y9JzSNvHpz4SMl291cehQseY9LDOrkkYJmGi5pAmEjJKliwmbnKymyCWwUstlRHcEjXXepBuLmN1GVJcyQAiEQneWRzJDWxl+aSrMR/OlwP/SrSZh7K+UPjoQlpHRjXx0MziMO90OeCff7tMB/zfYvR3Zygkhl5RxCq+dTkC0r2XMaDfLyMJXxrolPs0ub7kFtLkFT1pClxW1DWrQIl4j2UuZqzQRxtJDjdY5Ml+2/4SUZAbZU2hyNm1l4Ke04oj+bdJaxVCavXUMyM8bKrNti8ieGwZWtUfq1h69AgHESJUaedsJvYAqhYPl72C7sbGrW7WFlO4hjSZpQ7MvPfshOKJD+rAy9kAN+tjHYE90hEVa6/YMBRNL3UsKqQXvNc3w8xPPfcZPSvPzU4q9hfkTE/hnGejeAbSwqkggOr0oZUw6keW7fhhfLmOFU0tLkXcZPGdyrmeirRXp8Lrgkf6c4H+PWBGSHhjfPkxdDwfDM2HIce5GVHZnHX3g93vo1yQ9xRRohn93yexGU3fHtOuZI4QG9Qynk1UpAuoQnyMVmg1eFfHQfI6EhEcQtAXtrFFxRKgWGdjaaOisHR/SAHnnA58dXpC7O/zLZ8ouuxU/Mp8j3pCvlP7bNfIFOW6JQckLJWNb6LXTieg+Ly+PCVSwDb6MXb4n6QnsVv9uk8O2kyNfRovNEzhv9L+EczcdkHylrLSxtkyBNvcuk/h+pfMBuyO754UyeTA2//RS70ib7dI9AnEkpHmtG+N+/ySlE1EG7hOefPnw5uwhOJ8hXO5woCMamz+BRVEBiRukLPHXeQ4oiqHDbK1S5O8LH+FPmRZWHTZ4gTb3YIv8dZv8rVvGoFK4bvFajzy5gb493LdHLDtZxAV+36VDn1fQhkMCd/hhU2tnzEqIhI9BaugHLa8JlWAYF9QlYRpiJcZP/IwUYbdGwX+xHoWQ9EDw6ZO8wvIJ37QM+Em64JhRUo6AJWw7OeJTlsQoXTBaOge+yd18mkzPOuHL1xVBlP71FTVxyCN6yIL3wGoz5IlhUmJcKT+ZG0NemPkNE3k82DP3sFhjAkTliKjLL3jIbLR1lBcmwjZSdvzCrdDvrdDKc922+Y/MRJKHJsOrhe5qkS2htd6F0AOjrPOYkrzKSkO0xvYs42ppT1HtxMu42LBaEakQw6Ktt/EAwQcGpr1QRAQJYkKp4YuU5e34FDHVy51uLpgUBNK6D/6WPvHFfbU5JMFrsulqwYGFULDyhWNcaekDR+FbuhNlkOzEz5DRG9IlDphbUkppBpZwYRUfB6kH+A62xpGtcZxsXUEgdpgvFJ4oL4ToPxXCy8Gqg4i3z/RSAE/NM9+HT1gQaDSEIvHMgmojLglOkidPktoT70hTGmDLFM29LcEpMbS5p6OB0aGEfC4cabuNPXNbuVRaS+8YWbHMkVNm58jWRYFf1PC/DowvkyEMAuOFxPqCIkak6A2DxvPUIZcpnJl31IAvUrztjvqwL/CdGTydiKXryJ/utFkwdD5PrZcP/CSFsDrwG5Qt1aUa6kmFsH0CPj9Z/0h/FEVAKe5YdjoBEx9Jc1uBeMH4pLyy6yd8S2fiTF+f6WoV7cQg1UDp2UP58Jk1hFdw1/sbargFJIrw5BFZd8cFOVTwEaYH8tVswbVcnFCvIbDpjO6pwi0RX4ALXfcvz28pMPLTwA5ZGtBIJMxMnWXoVqYOb57PyMAYfjdpugWC+PBEXzkTJXTDRKTgHROCggslUi8tBX3g5L1hIXxNYn+3USEm//lFO44rhPyROyWKd6O+WxY+fkeBlDzijyOAyXOxFJfi4H5/kuCIIw4pcrr+YlwueBNykHhK/1HRpjr1fI8uxi5E5JBGX31Fx8wH43PyBP2gPx8e2y+k2OuZFn8N4jbbnIXK/7x17J+I6YpFPOW/fmBnQnA5/oCwKPTBfcWYX6n/unw9PoCp4EZpcaTvSyzohWXv5bNJzzqcDs9nylPvFgX0Scehu2MZVLkc6BrQrjUTHgyYBjdaHIMjfXDJYLMzl8PTMwwXCAUud7bOyh6mKMPgncqOh9/pcQzXS1fofBhpjWQXuHvCSOFjYqSux6SC8D0sY2B5VyMCmnTeFP6kBZm1USFizJITuw5K/FU6NdOO+YB08VwcYFUxTpAWBu+rG+qd/GLEvTpG+YvOX/aTTa2J+A5ZF5tROtczhnkKJ6dxp+lgODB6+o0sIBUtoUgZ5iYyfux7SvrR9+j7KmH2UPkFYJVUe2nWgNdrNyUiU1sQzwyF8lw5UBADAD0pkeweCiL3VPalFEbUcHU+e01RXdJfgdxJIwK30pdBhVUzUl2qHdow79cikXi5gKVJ4zNeEJDdK/s1VQaM/IU/7W6Fruj8ieHqaWC8O+PisjKDooS3w4dP6z2Y6ZX4zY1QRl6c3Xh5Z+oFd5f/+T+b+Oef1V58+ba2xLp0OgiymBaOC3qRl+6CXHyx0KmyBVz1GPUIPcOEX0OxYfeliyulPxo3DNcuNgwQEc4EqaLw5CRtON4+RUxNKV7R7yPbnP4FA72Lr3QanQxnrCsrwdM5TITcOSU823quOwagh3xedW2L1ivH2MrIiRcGeYFWh9Al9E74TPngSyBGAsK4BAZiWRgypb47MoOxO4E6hxALtuIGd+sVrYYg61TFyBDjYbbYF/RQVcaFiZYDJ1eU6TAg5Jmu+AGZC3OKyaGQXvwKQXo2u7fS7eDPaXI4Oe0/Bn8mYfxl6C9P7KA29ndld7y/IaeNxM8qHiUbU9Cn/AQfbskHXvSMbyqqaHRAxEBhzFmvWqV0doAREVZWxnhh0e1KYcjLxnhAsLdu/+6Ni98XnecznU9B9uIvugKRmReM6dkYCXvsbiOCuy0s4hu+ZPNJ7kv3qjbtcLGq9HKQOxzoiMbhYv2eBsEJpV7RAhL6iAeknUNOD+6M6iEXH54IbVA8ukgFaS3yRSTiNUj+Rp0n48UJA5yL12wK8KgURLLN0VyubaXqMpjA8pzISLk4FBuICN/vHYrK2FRJhiMHGKTOColPS01z4AKizXLhQ0vvgBaqce4OOB/YfUIFqYRFXOXBIezU72jtrPF8cCLZyH0exuR4f1Xy4Y0LI2spo8fXsnV0wOfT15d/crru0Zb4rnX237P0xxdU/2IaPDKka+m26CU1jOGAJ2foUJLd0Rz99dlThr7xEXxsuNz5WYLpAewLG4sxQHnD6ILJCDHZmrTAVEJ3QAQwzJSq2YfvCnwXQxjS0+HrhtjU+UL6mP1RVmEj+kAKLCWGfHN13x8vB/yI9IxfV89C8WsZ60LeOnfyPDh5MalRlEV+HevObJGVX0euRGZ0qQIG+iVdLjq90C5cC4m/jcWTNE59WSfmnri/1uXXJ6lzYFU87v4sw1f3X3iKO7O4xAXDe8WV+Rf1rEjW/WP8UzElcdHFcE/dqUcfaZwS/MRXpkuaGCZj/o5ETomeKH0dwStlkVoTXYoxVcrgxSCzlGeseBiZ4mPjlZ+fY9ndlpfjttPuw8b0N14OhnDgo/1yfGI5uJq7/b0bP814/MvAeFYjw5cW1i34evTlG8BRDcvXy7jzxicWyj3fCpcxODPfelkS+MovUV7jzq/dPrh3w8vZ07JmD05eviLkaDCjNHCWO+KAxi3O2vkxX3Teg92kyIkWpUh4OZxAwVQ0LjFzWljwj80wCmU0rPHBIEA/+KKTrwE0n1nn6QTsC7RiWCQovc4LHWQobRrufHco/hxynQLer4ihMHmGAcsSRpMjcuPki48Nr5zCaKbdl9C57YM9WWnJTUabiW2A2Sll/86IvomC23kdMEX+unGa7jJh3UUBfW3paYerKmzD/1QJ7LLq1C1r+MmHuUCzTtKLnCtTXkzxYDqzX5OgrntJMm5Mk/N95zukhS0hipPuHpjJluCovtL1GkBsy9L94DsyishYECFpOsMLjEpFXipKonAZ9+lJNT87YmCXQZD1WmJ5TZN3ghWH4Lg7Fbd8TcoOXZ+c/efK3zfbNaKUoWkhXOczcpr2qXu6Ftul44YCbtJS2btKvpr23xEl8+zq2WrIPvwQQ12kgTz4uXZ/SNtuWUvIj+Cm7Yj0N3OAk45vWLinQlHyOZwsjBWBeSc+dI09Y3u5ysuRlPKRl80xkyI9xfyvUOHgUHCN0tOrNNUJTacxsrEMABb6vHEomJaHqb1f2DDr9HrYqTiixk18UrIhxw7CnrMSy29KHnFephMLkPTHUPNqhHE0kzjKlgIPzVdDBCjf+TOVDi+6lWF/fHHlLzVcudAlhuTe7dlV0tuzdn6yGBTslVFKDvC0siFpREuWw7qwX5oqNUcFpXbiQ3lb+Im1XBLDqjtK+OSTc1ON4OAJibR6JYQ7d5f7yQmvyZ2tegusK6EAyqjBKHz86TVzDuwyKbG4NH7QHjpRQql4EEK5MGflwI4KVL8vOoGOQprj7JMsx26MpA3igds6LlAHVYlZ8kuu+KYaGfhaOpb5rIo65nX5igq1XN5si68QttILxfD1YiWi0N2YLcG3MYeACcNtrBN6ExUDl9r3TNMqdYW5M52US5UiKoO/jib7Yu+m1NWHOTXPtR9Yg9XMZQpBl2jtpIHiCpdyMbWgS4W7rcc8vSCKrMLq7B2CFzTRGqx1sZL+kjYSpei1bByGvKSSTiDru/QdpsT7Lpzte4X6brJUEx5f1v8TnNOBrCAZw+2AwanRyRBTHT0aWpGY+lHCT/dklRpnBXudPE2ZuAGgOSiEAqXhNDnjuRnDIKGZz81pyB1t+Qmz5MwB3sFqQRjQP+t9vrNH09a3jQQztTfEOEJCC9votCM/Z3G/bYpAAV+A5Zy+W0e+hVVq3/1Bzdx3mdGX7yfC3LldgZZrr3p+0+RIB/vjKH5KL7rbRocHi52YniGS/HF/5pXqBY+VEV+HCGdibt2JcO4YZ1u3Zc8Lm3Vfe2jzibRNIw1OTukRnQsJ8GQyP+En4vW1hVYUekuJbMoHPX/vYdzlDgc6onH4L8Q61jxUfh0Phu5o+JuJ9IDZIkC5uoEnpa0viOgK5juFsfouJO0sLTO8uQTuXBK+kw6B2MNgHWBsMxSx02q6yUpXqTM/3R4TNw1tga/hiqUANegWEuQCeXJBxxV7bkV02GcpGBbkiHBNgHuHQE2opnrVs8aPU3zwg6al+wQlZIOAbpFAHfCEdCD5xJwoFwdUYHv9pbsTEICF3i6RoVx2OxRd1USD/2uArk2P7mIf/lRk8pCREUOW0Qrt3FJnZPFogeaeUBwW0v7z/Rb4+x0zAILchCEo9UbH+NI2GOHcpnBFCkjnEOUlSOKgAIcHhO+87VXhhW94MNCPyJTGyLeBDBti7/YLiTH9hODOT8GAcD1fkk25x6oPH0wum4PuMS3c6yRltxMyu261NnexO9t4UWigLICJ8iu+TNEaW8Xd0u08Pg3pER2iyHIHjoL/5FaWPaMNGfs2cVeIXhqFpSq0OYIe9V4MAz5FcgPCjkSPnp2jN7RxiBs/saHVmdG3zgpGfNg0PaFp0Zv+noi4YfQtzIo08gsXbGzuI0crfWTlENcpQV+RjO/wxivarBsYxVNy82U77YhGPma4KxdKqdTuodB0k+E3sk2ECk8DKYG0pQ8L3X2gqowfyIv2WEJSpgsEA5Gkw4cOW5+4K8LIwpEQHluSEuGxnIa2QMVHBp9SEYamgZIFjCVQ5ILGOOlWh50KEQ9MK2Zsgfh9ZjsqPckFU8LCkVWb4X1nTGY1A1zDg+W/ECapA3sQ3KIHIpJLBb2qjyFoCz1ArpHx592/irpiQld0KF0ZrPfBaI8ZJ5VvAPiaGBUr35ftqAiFCLigW5gybaJb4KuQvZJqwsAjOKnQNRHqbWZYxEDJANaBfU90y1Vu5Pyvvi3GWZRy032vWwSXnU47Xqcjv3ZDAT4hP+jlJ+pgGbLNDshuimWnS2dK6Nyh20tXf1ozZsl61U4CrFz1F2ZTluPnJ4KPLJLRDoj84p0eMRy4MrfLQG6J8BKjCZF48QuTIn1l3YlJaJXRbYoqgqE/pEYcdEdVa5EYNvb1Hii/SEch0bDzhAarztxb0tfljKJWF2g7CLH7UxmyRYdxfAKuVl2QRfvq+8+UEBwo1TRgdD+O7eOTBfbJGNibxadXGS/s02/24euMc0S4tyR0qJp8Y0mDk1MxyngKvfsgvUg+x3lSo4WYSC9KztIpW8OkhAUlzA+LoKONyk1ArOIG1dkye50Eth6VNUWhh85wQOYIPWZhAtXwpKu6qALUCAM+p5+xwTRI8YSuR+j/JguxTpL2O8FTDGkatKorhK+2sh4CZYBwucOBjgh+YZOL9LvwecPAdj9IbsisR0IMpiYRmPzBj/UIknO2gGDGHFtZCbYpTcRGzQfpLvfwxBXCWxfa4nGSZjZbiyDMFi5Qs0UhAtax46+wwqEVqoBggzmhRCeFxz0oF/gWPw+2jS29oJkUeOJE4E4DBXqp5Ig389AI3exDmTbrYgmXDR3kNhgAFXr9YBQ93JcHPX5CIV3I5iuDF7W2CcV+nGkFRQ4FxPaMuY2gM9Q1wJ0dCaESnzKnDGcr1omEtpfqLaAQNKNkDxihKaXS4m4JdmCEQB5AA5ZkgirPHQgle5EWC+Vj0Q0lRpldAFcZabBmxpzOHq94g2kli3yhfOvgwjzK4qnVeiGPECxkzGsiT4SOAMfIyxlHNXhOztSIVI0+W1I1SkCkqwI8X+gsbXxx6WyjgvTOXjVa8xXyA7Wq9BpRGTUyb8pVFdksMD6ikgGVlCJLVhmV606IkQZrBlSRAJM8guj/IfdvXcJXNfpSxtzbdJVZHM3IwVoZM143DH+kLWPOq6ZdJHKy4UKQsS7SHiuvIm2DW6iSkPaTHWkQ+P0P7DIMD/zMhVLdvuUnFgX2rGVMZ+4+ugcbkI+m3fiXoR6ZP6BIfHARJXzIUAFFwBAu9UifhU6YPB/eadGhobSiaJ8+Pj6kzCNPvmz4c8UkhMg6WQxeUZhFZgn5kwwdbMUXdbbFYNXUtZsu9hqhogltgbl2LOxGJ0Os8F47epMhOviWJR2DjHgnBCVfp+puB/SxBaFvdVX1Qfoxe+Ay1NSv++yeILsVgvVuUOIIXWhqS6ps2vc08PCnq+7wDwjmhnWJq+6sxXOVHrwCZStV19D0jYrxOVeoDaoe/xkblpQtga7BDzJSvBfS6j9/GHCN76rVFJzl8MJxcMLYC2OERAp5RnhDzjgxQzG4JbyyCZDrQe5woCMaW7/jGv0oNF3cmxxQXKWZt/biquoICTQFO8PhihKssrwQr9KiYqb5ik1yIqGLLoR3YW3ripZDJTcrN8auJ2VMBysun+762dnhf8J8cRkpTwREfaPi6MS+i3IMTsifkR5v1Di/YlmprdIV0My9f9dpP5BIGMFB/E3WxIj0/C/9+cu4E8I6SRUHL9h3umKy47qrGlwnTEgKrL7x8qp1TyHFvjvgCfykdI+E1rTtBnrwQsi5VUAIyYgqY3rxOAlBFrXpRY9f7tXLP1mJCvdmkoixj0vDCG3ya6Kwp1BUZNyNikjpEzRRrlmG4zBslNjrDwTDE2haCpxjj346lxZ1O7dUVcTiSaEG0j30m2r5B5hZZlM+OCqvbaSDDeMwQXber77965rqRKW2K4+ZUaBa+BUKCtd9eVbxKTXxRMOLe+uvtu4qYsuMjo8XT7+4pk9sK7tKLEgRw6z6NXvhbVQOeDlycgN6acblgLv7tNvfHTqnsFwrNbibhYdsuWMms/HfzuybtAk4OR23BPHMOpU9fQxYFlRijw58J+/WfGivjoShGaHImkl1n8adTjuqlg9DU0ZtsHo3zYHfELF6o67311M/yAi+aD5ETs3RkwE7WK859bCxuTyVJ8LASEh+NGPjF4lW+L60bG92Xnhj5ricjkFPmCW4blaIK4soy7jIr9je2vhls1HKXX9whMRSmp3cK+8H6ZQKagXFjTQFt5zd5eG9/lJ//9DZQTZXbHG62nqwiL1J1B08mEhTth0/QnfCQtAjZgyz1zOVJ9tf6+cZEYbdQip3d5/8eeBRsAI8AgtIxs89RDSpvREjq4x4SOsAHWKVg9MHpv0bw0MMXwUilOGGDksYg55Os5Ggti5oeUhg7tYRAVm6FZNcg+6msQMkBj0tziC9Kt+d0X8a2mlhpIXeZ+97KEucMzqkf4n9KYPqguO7uVVKaIWkR9w6OXDryMDJI+5qGtBYi5wZPM4bDTImQisHfAOn7HAC4T21IGrqZm3MgJVglfD14Qkc+lYJGut8tpHVqypxcoRisapUT7OrIGv0VO4ApSRh23TZIOx0ckQaNFzAnCRQygdKQFoNmAFQ7QFEW6OD5Qtvkhokznzen9ooM2ZBsS9VxJe9UlDT1MWdU3FXZdM9jfzpr2cOjxyeUPwPMpWEhTEUETp6eYi3YYBwucOBjmg8ci+Z9G8mL1MjgyRlkTu0lG1pWaqdM+ZpBTEqGCI6YkMczEMU8n1dTLAVzBEGzJoKMYGYyJGnhhnBmc2NDzE9uOo3RGmexg5OGc484YHCeYJB8iPPiRnQTR7iJ9J86i5oP9oBXPYwq5yJM0NK+1SvmuBApxYtI00JPbpBe7X21JywNjbYeTQipGm2yc4BelrWQx/SF/ZYgf8B1pzz5dW0zFBnpS+3Oq9f4vKl+0KlPLHc0peRtXFynQhBTPsN6JeL9DcdXnRGh0ap4gFVzEMg4VkvowCkYepxdqPQJ/dBDliyE4nO5ZA4/hHiApGgtXiHrxox9T0ktKND0u9ULTf/SJR+kUghoeueYZqJL5t6fjffMF3d3dXfMU22bsK5AZHSS8ZpuYMeyMBUN/4kI93SmZ4yjlL/dUL/QQ1thmfBCpta6Pq+v15ilD14NQy6150GnMihePRw1u2s+/N+hpwanvGKr+ACoBJSW2q6/VUF7lae3Df4TgYhVRxGPGboaynRpVQ9id7nga9H325IZfLwsYZIen74/nqKRHnIu1vaGbolA9VCBxkGonrK6HkJYG5q8MMElQioijJ3v6luWm/zs4r0TDoKlwPS6RZwdheZq3BixsS20Ity9tfpuk1ZmewZ/dIhQyVAYXHo6YMwtx6qYWM85uDyRHAbNnY5cOcxEMHD4VMNGLqNu72Ug4P//In2J0vVHnak95hNGnQXRUJMx8/PCS5TepBsMVEAC54D9OGGLIUbjl4rTpsaCuZjhnoxxdKh+sy5IHovlXmgRjT9VVsUGxULL4u7jFDuMrK4q1OlAuRQZ3RcJTTsXQ9+8M+g/Wf4lD5HDPuHuiJs+YacXLNV8CgUtWzdAyEz75+I7de8YKJwfNNz/5sjU+cbMZen0jLJ6GrImK4cu6u0eaDNyuWoB9oXI3oUIc7wnmdHjnpgvensGFo5U46gO32FeXrH7TujO5aonuqtG2lKDk9kahj8Afvgbo49YUInVhiHNoy+b37seKiVESNj5jqB7mTdn+c0hXRnvg6vhNBI12Z6EZ/o5YJRqkaM+kfqBTbAZy2FMkwkTy6eUae0ovxgt+mo2qSLv2FdXw6nnWyjW2OU/cZSE/iTuad8g3K18gXT+crUwVGODh60rKuvdHyHGtdfx1Xq1tTOiVkk83Bc27mKeNCzh+kgUU4MY45MRE4AG3lYjm7pTWXgKj187F/9YHDgW0n4Vr1kwGjzPFCx13P0dH5I5wQp+ewiYud5pwyvcEB8LY6tGrtPJLm5Cf9VWOmTb6bQJb2EyZpJoxNwgbSubwNfEmimAuLvXMkCng78BsbwbAx6NA4C6vpcoy7ej9b8jsgsoe3TxYeEglWQMUTklRDvgXCn1TJh/VuZy4fKnwYTQ8HV7NGPzxYaCBNlcjjthMyv/Nvp6Hql3J2ujKBpnfGWGY33GA5n7KsB5yuPYfFyGBbkOnpjR8yqDEwllBUfMioxovOiM+iIhLy6dRkD/X3w8U+mwyHb/J1rPC2eE2YAxBvq2whuHphxhHC5w4GOaBwl2+M0yjG2QyvBnE8epTPGFevG6DmOUfdGEj124tTDAySu9vgRp9r2wtI+MEpPEMrqyshdEXsnFXBQseKr8vcCsSUsvjTmS6THwj2BYvAYijxQZ0wdBGI3nXHIs5KNO0bXajQ6AWtx+LL+Zfz6t/FgspBgb+HI6g37yERy7nSkWqGAyy/eMVXTXwaq9Mh4qJMR0gwOlGeomY3cztLgRnpC9w49qpHHwDTA9y6MA8bUMtwPvI71Qt7QqrkG4thGIwgVj9EyQsSklUAFqLFtXysHfPvndKIRmQ2r57p5/JKw/jSmdmI+EBNpRoWIUEkLg3dhP1YDumKiFW/qDfGWEjhe5uzHmCigfSo0RX7Zht0NQputgI2pcsHBF70acESmBrZXCQnIpRYju2px3PtN6YmoWSdTwtSDBiArOpRjWaEkLk0gkrB2vsl/5IlgDY4mFuzas+asSOBqK7AqEX/VJilEVtpErpyN1TtrFQfYc1e6yhnlpLIXr+SPMtNVBqacWSXV4jlKMPK9xYt7bUeBwrqawJ72sX7agtT41K6aBf7V54T9740/bGfNiJ7Ai7X+C84Tjfyaly36t8oq/hc+rIEV9NidB5PtyXj+Wc/vRt1ibYfUxNLAWkuhHAN5hlgvDtY7aPhBLPXDSAa4VtnGi61SSi64UFlVGF8ToVyh+2O3iKO0pAdguTsetYftwJB4/fnsigX7mni2a0M8MyzdxfSxFDfM9sahQlM/7uOXiD6xyC1gfSOO29UCbL2yeJA7HOiIxlJXWa8w3q3nGNGL1clEU0mIumO9OvHdkx2FEGesRiig1Mb5LPkDmmYR2zIiU3lP5HntbE+x0gTvpHeCnQ9xLibt+eUyoVlvhyKscLRwf4NRR7RyppRpl3RqnQbaXhNlnq2zo5NguG9JcCc4HLD6buRo4zGhBx/QI530fLknVCTi4oFiMJfELaqNK2aC9JgiROWiG2swmN61cZUwARShRKYSw0rFXBG1UlkM/NaGE9kh7ztvm5GqH82w7BTcx5z1Rj96gI/aclgZCzIu1FmKuCF3iUXhKpsaNtCH/ZyYcNLqDiCrKph6ObCHpSoiwQF0RR7ImPqOGDpF3sjQgCU5T7xALWJ7jkjojUo3anF3dWLmyhzpwl9TGRVhG0maPum0E760dDwjOxb+LKEgRogeI9GI80lj4Vv8owunw2LJ/nzGud6qwWq1FbQNrdsTt/86BEAMKIGNUAb1xLKOBKsCcXjWHz08WV5fzxGHq6s8GLb20+am3VBfkk2e7aCDCK2+aBNc8fOzyds/6AndTtPb1hpyniV98yOmjU7EpFe4IK+qGe2woM37CvzZD0o2UzHsYUWBCXChW4Qng9tJzQyyc7KMKe6UfI1UqjMfwNrxraOv90D5AtihVLdOktq0Tm9cf21wcuLhyXo3QCDgCFJF//z5Zvni1sFi6SjvCDXDuoQ7YSJsAHSLbrzt4YZ9Yzd2AW/cEyxAnbqb3o7FiOOvKwMigOO2bnopogGmp256lRDhgaGp8MyoU8Kz/qkFu1RuembfROL7Nz764Cv2sD0RxQjii7cBGSJSX3zhy99oq/w8iVvl17Me6ojD8JrFfp5NMx41ZZrFHC849LYZ16j6f2584TBiNfBWQOW/feVhEcr//gLGk+Dfr9rtmaaCf79+f3sPYVkmhPVy2+kXkZOHN1t2uWHjpkro4ypi1RpoSRZGxiEmLhRehbfO4O45EfHKVyx3dFhvWEBUGR2C0/4E8qACXus+FL4Bbrk4LgT1dSEMHG+qmHlx8p/5ibnwCwsyuMKJxKIdeIzkLewRV2pxODntZ3Tdgu5S3mnccYOKy00vDsCzlSn2xG2Cpl99ix2XLG5o31RO/HFxK26yUn7yCQt15JD3JrUcfkEIrDd94xULDc4gvGNmWRay2KKZni4+8LpFf1MJjltgH+4Wqetyu90gXO5woCMa3yfMj/ECtNvdPmKxMc0NOy9VRrdxK9SCyctV6v5URIN08aCkfsMZS7cERyiOif0FIfqEg7QF8AiLiMKBugOewPx5+Xb6SdR19uQ/LPSUs+836Pur/CJd4S/UjFMLMe9GZKFkNwTcOG3RAAHgx8DHpds88AsG5EoFMFB+jaShgmjDAHMF1QhJlniVxk0VOvn7AKvi0sEOnbvhPjqVFfEo4wPa0cuaKRemBQWaxcJbzG7Qe7pVvanJfqy4KfrGwqhHmaHPrHxBb04ZjU1FNqkDNsnd6oibZ26V4usV5bHGC5xHHtp9w/79W/VLiwUR4RXlvKLj1+DNadlp/3lx2+Bwctp/XJb9922njbYIqPSvkY0q64g6h84G5KqpQPm3Zsa/6TDg1sdb5UuXcLbtr4Iec4y3JdqklhK7BdXrNyFWwrUUPII6VyT3PSsHgur6jm5iH8nwu/Oy02lHuMUdL7fHYg3Kq2WAOzak37seWRSnS9xxrZvINHRP8IT7M/Q827kzZQrldaUDPDzh5NY7D7m6W+/wTmuLDJU8p/6Oc1255qUSOfvOQ6gbnECRv8XFoXPijxPkzJ9elHgKbehdb5Bk6DaIQLl7vR+lfD8epXznUcr3w1HKyvnIB8f+fmy1FuDz/LANOV/JX38ISanXiljdXjga4S7dTqQjvH1NHQEhuSD8ARqTd8QyrrpRaRlUCYpsgpY68D2gBmlgodSmJTu9SDvQGWMcwVFFCquq77x37K7Fu3858l2onAXSmQ43ykTvkC/9xNt7Wy9C7goZD2acfHTX6Qa8PvuPzF3sbDWAb2XsHqCNX8ysoyoaFdH4YDzzrPp7eOJF8PN1TphMFl5tLvuOFkzlCHhl3G1514US2KYIibI0pgItjftYkUwKX22EI6wLX/ZZqHxVDoSKg06ELWJ01SmDLPKizc7doye10Awvp2yt1d16IyLOvOhM2DT3BJBMmKm6+7GjVMRWSRVHZSjIK1JN7G77PPwxBI49jLtN7t9j5lfa4opKznLcdZ8PMiCOqlVprZMSv2pjbaDXVS07nd52LsSXw4VPPe1DbuNw7W0m536/Q7jc4UBHNJaxmL1KKxg7tFB3NOD8BKC50BMtMBPfmISpPCNYItPwEoF7YrlJEWc739EXu6e7H/UpbEmqFy2Zp5ITb6jM/ZjGO05VvGs3qgHP5rtnns3AW0/bpaao3gxn8ua2TIV2ft5OYcfD77tnbAnvft7vfT8xr+ELjq2txyT1HYsc9yfyEs+vvj/xXU+Wv1dn3eH7S4cOxR6Havn9ZY3a1J1PkFu4GLUz/4Ixt5boXnWrcCe9vgjwokTnY+IZB9RNZn9FZcbjVJqa9KJZTDdOPC6uwbgTXM4Q554WXHmamGZto5ofkjjR3wUPYNvP5KfDacHL+IA1wjUjxY/11cN8/Uc6F0CkUV9gklY7O+C8p3bmb4DLQiupmG0qXPlg2IIXBilWA+KgMkQCwWne2VqKRstOl/33Zdvxkxj997j0/gZ83hPi4M/Tv/P54od+Qi2okdUD2AQ4BZ5Gx+l0kSOk9acnn4eZwjmUoZI1gQz7r5+qcA1tLjH3/2b+tO6B0RF3N/AVN03qCbpW206c65zCGL+dTz+MOXuohL0tk0++TzyGDN0W6q7sw0uhxKcSxEKJdlXxQjcLSklAS4VxiogzGkS9Pw6JpdvaK75Dd7KT+CGw2LhvV3DCIqHgHBgnG4K2lYG0+8EF9skOF5vCM8L8gWkZ6C5bx3qKZ4iAK96niGPtGiBL4TSjKVIRUSjhYjYOoibs9aYGie8OnbxlmKBrNOl1l4gfXnM5RcoZr5fulM3eMTdE5CyRaaIVcr9em9i9HIMjYYC0bMxaJiZ01ZTu7gqN3oSlkgnHNIhcIUt3pheoGdrtk7iFRQ2oZBu5LoAZqdtBU1p3Ey4Q9ZQX2e7i2I42y7+Z82qtJHmiAZ/xQI6KD//EJ2vWiOzwSSMzOhc1pptVAbc4cRJ+ut8gOruFTGikHByuO1l/T5iPTm13vr3AuqLTQe5woCOCsQd4mhfEYbIuJw81V4kBB260EYGcl7g2IsQ1cUX+av17BXqAopTa2pZFPlaZJmg3TejLiMRdC0LJVvP1Tk7kGc11LxIPnpzQ7qu8ePgXD6SehRt3xl5kZe7mVd43/E7oKInE7M9EfX8F5oS0okJIbIEUbMijuPE7cqQMBLpf6CTzIBxld4eXs4lNDO12hZe4MKwBJjMnW6sXQW8qo7+yWCaeWyzkSwl6TDo0I3XNn5Lx/cAuAT/LvgE2hzf2R18MwnPgKeWTXjOJ+s0y3OqHN04oCgwHNSEmU6MSYbNpApE/4NsUFhJm/RUxt+r7wBqcdvru6IqlzeRdu3qBxI6siZFYUexxltdU7UiUqc4RKnVTXTpbbEZwMISaMGfFAzUmqu9Mlfqr0wtnuiiYY3Rbp5eXgdeeYq+FVuZ16y3Op/fTyeaF505v0FoMLxDWtVRAlhPUBf1y5BGGOyRPw9btV+HbP8TXDM96COSH2fvPSone9FAGm5ErVKrG7MFw4N3eDr9uUCpfxT6LIDrTjT5A6c6g8ay/GYk7d0PPbbFzx+3TSqaio0otF4cTCScRCS422TN347me4fUYGGfjxHCNc2JoIwRc87x9LvCrtCygpINqtI5q9K/zCFFdEX9wgcNbyg4YqpOTGe6B0jesq0FPwSs0xG2n4s+xQlCed6LyvRrWgA1FasA1D4LyLe6lftfuEXYvzXqCzTQ7knBEz6zHaGeEFfGg99QxFAtHXoojI2XBQHhul0qzqzjzzr9ZF00DPR086ZaBabxESv8tjfyoJd0IDFEJW3NMo/+wZSQ5D9ziNtq501P5X9nmqHhMrMpt5NP5QslnlabuwOHA0R8bCdHBH4qJ5FALCUHDhgiBgKKbB45UG+PAGuPYH3i3rx/E4HCwjG7LcEjsZ2gJqSJa53ByGnfaLZedFn96h6//Vs3keIi3yHYa7PaYLp99Mnv2yWyj7Mjwxx382TUVf3qD+pSyR/kNzfyMa3Rmnpiu4LF8p9d3f3A6p0xbDskMgyN9ZnaWvkXnqZG5SWzG0SIqRw9i4Y6lGRsxVLLx4CqIUnTwZzfPilwCMQwH/Nw5hoPz+IfM0D/cN9q8utlp8R+p2CL8GVB0SqdK8mD8Wsbv//z6BfYqmg1cKXrTCthrDr+BzZBu6+77NlLiXEbFOLGl27gFYNalA5SCbaMc+aKmPeiI8wyNQyXHk5PUqYiVyldVpEjlkodiZO2syJxaGfgHD00BIisimB+R1e2Hp+HH/is1roFlzzAI2d5OfPYszJ9IVPNOLySHGTW3yHaTE+JFjFOyORXlJ8+qnQMqu6BXF+IbQs8mI6BDpYChyhwCFMqod6iyx9KGMjz2nkGIng9QD+8HVyoudMeLQBv1jmUnx8mB3zFBU2DWOzNhx+PxjQpup5v9iEahFRs/cI3rrLt5EMilYNvzjIsI5yDtBPr4jTMpQ9FHGRNAhngNbhLS3YR8ms1N0F1uoBnTn3Pwg98U446vvy6Hm7mltsHE5NuQLbFNYQ4fOC16HizEsTOlEtVvwlo68F8HPjm7i3Cg045hx4OD3ToyU0Tex6f0AtzdV6wnGdH/O+3wVQol2aHLc+x99nOOPBJa6NHx7byEfd7dDexLxJH9wDhmBvHuMYIOn8gTAV7d8Z47GpbIKwyEenaNIqbl9ATRhVZ3SO+V6WBriegS+3qUklQ5i3NwSrTkCzfaMFHZTWMZE9kTsp2awMPWVI50NULGmcGhWpgeIL4RkIBLYcpA4UdPbr1MjoMTy3KU5hMec7/MLKV7UV2sFa5xuYjCnZSG18IOvdQBHgOCeYpkBAaLIlgUnSP28jTw7OmLXfM8Q7jc4UBHBHNubV7OEA/Ki8PHTtZgpzMOBxR68cqAOfXQNlOaCVCcEKyo73QLCdwNNqfbAFZ6927vyFFl493e4k7Pzi0kZIB0w0tMT2nmzrEGJxC75o32ozBmDDwSDxPQw0c2gtvQG3So9FaOjnR34lBREDtMZ98AZkRvdA87rXPiU97cpIVB2DApAvxGHgnUC2+M6Spl+J4uaAL8GHuhOSGiGCdpOWMPjPLqDrPD6B7yFbb768hIKlyjPeOitdl9yYH3WAuzZk3eXdUL0hC9eR1DpUusTc44b1VlnBlRmR4yWJ4pvGuVPC0EEjMkT8WfUS0czuXbS4jCyQnHASpXVFnpEZkdnwOKdPpkPyj/C0K3UFkY8uHu4znr2SM4YG3Oz6RzRzg3Y97+JX0uC9p2aKA2t61d/7W35QjlHXWOxL4Dm0nnyhMo5gqLQzGrd46D6x2HFM5+d6ORRUFdOo6Kqwyv7/RSD6W3/kmVdHuBcLrHXAur1ooLQRtg4l95BmxY2AXipRt0wk11dH5JI3rdPj4+DqbXC7+t0ol/9gfG4Y8zFn9FIse/MtYb5heP0hGaK16FqboGuDR26c7W5C7SZ7QmYuGqn4B1NxUW/HblJgudqsCT2hHEatjS3buZy/t6o88CWiAueNin/ZYuzXCDOZSlk2oES26LDvFL2rHgoezKs0tXKC8dAo5WZ7GsLYIThUvn20UMAzF3N7cu7ng8OBn5EUrdwdZdRAZaqZuO7E64c2jp6obqdpFxQVhIjBd+zOVy+vXP6YD9geHUXIbT99M/hfjzndQRESQ9MhTKKYteX1Wsrl/C5Z7r2dBeIsHCxwVkk2BdrMUPkBdCI7SEmQe/LRjBL2ENCwbXZDhYecrooh1teF6wNduIiCcKMnc7kRxfDCebZLQ7nG3oVuu2YN7GIczjLxSxKxqI5+rGyY9F72JC+DFiW/SuJb7+CdFd2sWUMJ0nHPok3O6jBN9jvxPe5csHgmvHD3xSbozQZy18f0M6kGBP9PXlX9PQckr4wOs/amka1NKvtFI22M07ekwk8pzkwmxbMxcdEOCuFeMAvFNSV0hvvNldukNUCXFwQBPRxgUz06CtBY9db614Mz67aWd00BfdM2qT8gu6/yIxL7qg269yoxu3ksAlhM7zZ5xHSOzYXjDC1U0miFtmgbjwIb3dtYelHmPfHdAiO/IUksXrJw8RCrNN6ywHucOBjmhsL07WjVnSGcNqoTpGlEVhEqI94ZjQRbpHFpAEVZolseeyaMO8gWhj/QLpyDFTorsjkqo6qhyBnJO4kVHIAwOV2AaTEx/YRp7ZpQbEsXRzLplhKaMtb2KIIyKcsYapjGBtQ4eI2HgStiC/TKDsn8DtJIsuLKJmS/UROmsC06O7eMOka4QTiRMWxvT8CcEAfERsepU+r20YWvDmeqE64VIH7J5esNam0hIV216W6q9rdNrxfUcmVTPgLdj5qoA6ueJV22xHhS1auXnzVOul9WyW15+KgZwdl5H0MhlLtNR9dNY6QSVWxJddQVZNz2752+YrlNkEnUrwEWK6YGtDg9TOVnvBjEmXdNk6aCnozWCIj4YW+zIMQ/uYeozcFPCCq/2lwCRhGqlpkUZO7qV4qb9N1zJF2DxQ66RbJk0UXEoVXCsGt7zfO1EHI/ktiY02m0tOrnjRiA7QfMrAzQpTmjHU8A3E+Cj0rJDwMpiQsRWVU5MfECdkgdJN1OaPDYfSQe5woCOC7WSLtCJkItGdVzzcf9iMKflPpXtBwbWZQul2U+UTdG0VS4ONXlg5gsmnL4SgzGDk39xMeASZVUYi1t4mPQtvAdl3ZT0414hZsi1t41dv4VP2bMIuZSrcj52KlFccdpnKE9vspBaCQw7QEtWxBOwFnuFsP45UGhI/5o+V16QXCz9BW5oqzuumjoLKO2YflXWi5ukGzGon7HLDQFCEBfuDZ8b7meoJmj1r10GzTi8TtUpFKF5tYGOISQAx3DFVtvp5dEZ87o5Dz6F3LqLrOlCPF+LIRYFEfwfI6LDwnouVy8IN+M67au8uxhMHh6susJ5BM51Oie+bIcJEi9VDvvAm6ZVTZApoAFdOx69tnzbtmNqKGDOv3YpfV/62v0P3pxLXDsSt3Kv0w/o4zGSepKS8pNiRy93U+oT16hRgpB93Sr4+c6fp2nGmYNVD2Piky8WfQMu86nLVp1G5MJMwVbxm30eoGJ8e8GhtlwrToJpJPLlpZZ5pl7cB+fXt5AYgV34MGScbF3ka4pg64YL81hQiGVbUy6sfGacEh3Wi7AiBwBxWWf+sMlqD+ydzIk+OUQrMtS++COr7PMPFtQxWXi/XABEgjWTddjpX1ihmpiPbitQAe+XXcD5P9ARX4zao+YD4vbd5/AaWPCHsT4dnwONSd1ZY4jD+NSDIdxs/rlKosMgk9f0lIlyL3mVL2wH7+lZe8rty840ODsMlbXS5YRJeEQkW0orUCDxVeQ28amTVe5ENcscAZR2k0EvoA6xc9VH4BFzDZSOyB7tyk44WX91pstBwJDp9oHvMEyDX4KnFxdZGdyecJNDYvdwCXinDY35iHQLKKlNwaJNqiEocc9uuj0vA2E2rTWKsGC6ptAU3IyRJ40I8uDhY2/RYAwvcmCT5Pg54AqNcjJjJWccMpSshaKYJsayOL94zt0Ys2DQ4ObEwKcPhhc2PUCl4CB7q+DSAeKuB1rgXJ7dj4sQBM02rXhhksRxtwRzbCbjDcsXVnSr12pkjI0SJF8AJboDVuyvKPkOsBuSKyGujhVB8YsGlJiuq9/hpirmrLtVuudpM+UoF1AYbCHGPO1pUQo9NEeFrVIh6Mq/1eKWAQbFgnSLdsv+xTtjQICDDIeofiQk+Vb66ssKbXnNiYGdrP3lo3poC7oxTQkmz8wDWZEPAlWf/CRQWEUN4ESvly2HbiU/jft8G53AwIPth/kIkmxfV3MXBzytXXnhCr65ySN6Egsia5hnPWKEcYNhih8/jV6Vxf3nCCsrqSxhGlifSeoFm78rKU2/hJa14NOMFqFVE8oeF8ty5FUtTwuWyDfZhmBgLWwvD9yOfTn+ZvsPEgiTELRbKC7Kc3hs0OcEpu9crr3dqsEFjfbVdIOtB7nCgIxrn7jyy7leuJGQn1d8NdAqlYaEFPUbpWaHPmnUKGK23lM6Oz2y0ghKPEEtUDhfOAwj3HNMI89AvwTm668JtOcYbsR3ORgPKUyO8MLCv0og3zohJz6eiI+biHGcGKfrrlMrB1r1mL1sh4IB0M2RHZm+91gcRHR+UrMcE8XDqqw8pso3PVf6LIAFhPCXe0yY44NKrVY8swfwxmR900H5fdcE6M14SD80R3NiuK06k9JkQ9vSIPR1LMjGOigwe7DXldbF9rqvNjIuAYocQgqLXeSBn1J4DEczqiCwo8nWWfjhWIcWw3ulSWg08vbjdCrHyp3Xl+KKy+Nf98OhVz3ZY+gpmP4lTFQ2s/KJL+co8gFvQPvBFVc0/p+dbaN7+sdmyP9BK/NP2wrYM8OfPH4iTVQx/dpsDHOiIxrXjBX4yAjqbjpuQjV8bWGT90WPfI21twupPtXnsP8+ufH4YWUKq3BxeO9no27gesPUa8z+YqM5vfXMntQYWI4xOhtAyavABgh6OkcW8sqrQLpiN0tPmLOKEsEAttVEHz3rrq2VeJZy7a0dPr7Y9QgAT3rkbsFPcqFvIFVB5YUOGBpNKBjzy8j9gukqh0t0YGDmKNS93zlg6Vnm2qQZFXkqeO6qAKW0THd8gOsq+o7ObDC6cHiQu5QlHWHL5Vg9riANxBcwQuqy3kJfOKZKwsCF43gFJ6ed1ZL+LRgsFBgWCTCaKCyNTTxbYyTF5+BaorHsfqQHey708RkiK5cbXpAoPEccrWpKs8wT4iWPx3GEHc4OT09UViZtxJiM/b8xlOEe8AQLyQNwh2NJ/Y7y+eOI+1e2zbxBVYv525UfqcKjc74lTE0q94uTk8/jNgNmsrPWCNTSKplqTdUHAPiXAT9U3QHIJc4QhfJmgEquX6iX47xfDCLJ3Lsho0j0EM6igRgbi/XptOf3iRdZ6AV/CaxmscGX8wPUwdIEUSViFMpp6MpeQlfFeD6tunEQM7HqV2r+AxDiaZ3/lsHSMBqEJj9I3HEKpMGAzS9Y7rlmUgwwGLv7CNXlESS+3wPm6ofkW9DYyB6+wFRmV5VLhtATq9ze2CixsGONJpybJWCIyZa20Bul8Tz1i5BNR94kL1/PVfhjRIucRK3p5vOXbdLYEGpmp/URsKS/W28/xDHnhXWLAE/hoP1IyxIanA5cj40vE5IWJeVVkJFiixR579DMOTdVLV75qi0BD7o5I3m13S2hbZl7+kXkcbPbLWpX4PrsWE/sfMlYzM5c3GtDKAn2zBxNqBpFfnqqgj1QRc/aLVNT0149niDOKRML9gjlx0dHotGO3Y9qxAJHRFMaUjty7gWmRzoySdE4XtGyCmwOUshtjTCj8wuMYMjVg658O+3QyNoypDNtOJyI/+nJfeB6LGhg85gaFL/vR5s3sfryOP+AbOZBQQhz0bHqSV3apX/j5rQFDYPocsX2IbK1ich8GzF80QqRgnS3bGTI53Rd4zaGhEfydUKD1eHxH9iLQgKa5c4tuh0SCor+ODWgHtXahytpUcPKnFRFe1I6M6qW7E/ggLkehEp9Kt0AN4wN4oT3RmX/Sdg6041RSxjYalROfLS7PqKoFvXVKqqiXX2SEv/jDuzv+VOLAaEJnkBdoKdBm4nqqTwHpdefh50/DD/aq0gfeTYG2ScHeaBdZ5oPc4UBHNLY1Oey8ydszUfbkekYqVNQLtcc9bhknzWWcV58rbjrLdaBbqqAoRZzTlOsdE2aZx8rlind4NVX9ypBc0wfSvaLngMszVSK/NSKih4O5L5VIqqrnePFZy/QVbm16J+OMs/xKY4ggq+9KZzMVKt/tIiYieavZerpFRi0YBQiii9EogLJbXjLsesoIuHZwcg32RUYn4NN6lKUbpDB0kd5EiB7y2mGHiDAWZcvhaOFiu0SJiZbL5gCP7xu8vuNElWLZsOw3Uwl2I6F0/EQkculQRRd2PXWozyBNOCBVidE0VYRihpDRzFzIC/aQ6Up3j7DN0d1GfMo8M0JxWKjA6qkzrwyTEh9ZC4M17y/f7bYO9bYwxgVKmZGvW0U/gRzxAenH0g+4eLXwZCwFxqzencNYWPoyzx9kvLed1XvAC696Lh1TcxnOB+aP+O6Fr8TJxUoYFSqiRgQiyy0sCTq96h5usdh9DIUHUxa9uI/5cT0wvy4zl2RsvhSynbCF+p8KCGKBHpVQQVg3bG8RqrnnFwgvO/3YMewY4aIi1epDq2pYPrASXnB7VemeoccsQuk+6Mxed+5b6yfDwRmdOFVYCBfoBagBixpA8Igdx0KLxE4BYwJWj+tNAXWBnklwIfLKEEnnZNWrUW+dLDVsDEfDEzivMR1wAW+U1sNScqvt5ITCo8iX1s0Boed9F0W1p7F+q5wQnxdpG654vPIzBPCaOuMuvNJfh/F2t5iwrQAizjKY+wwwLJAYugjxoBdpvDs9NQwudHpiAELBzegbsGaMhXG2qwgs2wshjYJNQBZrDEQMC9YBC68PKeEO/RPV++bgWboMup6Jx6SEIwA8SFSokNpsKt6DGYgiYzodWx45HwwoUaFNzxlm6P028i/DlukG0Yk/PuhpfmDaGmjp6kpERmXLgRGJ+jNsNltaeJVWg9Hm+BrfgQ/r35XwYAIJ/CJhPbVYV1TElXMWwh8fDMXHi3IL9EZXuewrXKnV6LRjIb54B4YYKLGkLiRF7z6C76Houj4dWedCwTrHRe9ORH2IeTCVbjHg7sfSzlb231nVjDzyvq11dLYBozGfooabII7dU7oQosPLnd0d+L2+h1vZX50zg1l482Jj+ln8uyofwcH3jWjHqH7S4uXv+uwYSZ+O+C2g+I8BekBKjLdw9SvdxRDdwUSJik+o5+cE7Cko3NHSILjDyYnV48hPwBHhDSYkQdDLdxmCbHfxKDHG/RzRhvQ7v/BquIo4OFCoZ+rHQb4zOyfQSMmNiMLsFY3RZTojgKYmoJq6/mOeHPjdqG9EfuU+KjXomtVuSPmsc8qFFgeXFd8IbbsGa3Zmp2aMn4kFAmfFCeCckDLiEmqBgVkpeQFKewHamy4ZS6PqGtO2P4QmZ2T55uKlEhvoMfdMxKx3LuI1sOKlXEJjRSrR+7r16enM5mR84Qn0pSUlMfehlcgdc0el3X3u9uwYxj6xAxfDjBlevYYNcRiHdm4OGLONjag8UfRctmc34S0T6+pGdAHt1hLn8w4n0Jz4Wuan6N1dpRep7D+X/RnsoyocqujZkAw9Y4bz6SXyzOwS05mZP6LQRJ5SUXz/e0EGhZJ+iX6adpGcpsMT4w+I7bV4fH4GZx7o3MA8vofgGM82mSzEbtI98j33yDoYp8uqXP3RlUoijeEQK4sCqDfvr579AD0YFA+3dINhcl3N4jogZQqdKYAK2ap4A1gxa01cORKaAy8J1ku973SJZxG9HDDNdKrAk8yaYYRqUTNszF3NsP8iFVrvyOiaw4BEmTHb3ODkVJxeIHQSFPgje5Ezjm1r0EONqhn4O86RK/u+yyJDLPQisa+uLN0V6SpUAfBZoYD0RMsds+NEp+wFL9LVfIEQh4ufuVgOR/AWPwi+LMlmLhtgoaMxPFLVkJ76IQWbGFSeUewNna+Tf0LiYociJuEUi81Xl8X71RYLyVauSuImhpIuWDY1cstES2pAAe3jbB5TxBfWrskHQjhwQKWHWqcW0dNME/ar6S3NDLIiKpndTs9UQZZK2qXN+Ab09vSKTWy5YQWVfIQsfWYZ6yfyEukpNPcULg74qOXG3xZ66xK/8EV+jZfgk5aq2+cUSL6rRzr0yGUpJ8Zv8fozbdRjL6nCqkqJ4BdhDlAASnNKHDsr8tnMwxOK7qRZMNGxQh2lAQO/Yga3QP26rH4QlSKy5OpdRiW340jW0B1s7rfdYST9ZF7npsjKVxGBXwOm8ZV6h+SEV/potxG8wYPSGltbwGs2BVg7NvJPjsinCvAYG9ganEBYYDGqB9T5m90iOSAC/LHtysRZJ/QTFUCsdNfEgqE6Zi8Sm4qVOW7lxnMhvCGzy7JSs82Ij/KIKyFf3yhrRQRXT13oGxfVNhmwcbkZzPqPXlMKitDPbWge5tbBAWLDutAizbQ5LRQudzjQEcEWyZvk1xcpOgQSf9s3JegZ36ySt25OtFwo9XhQt5zcd+zTUYLiFU/+VKmLZnwo27ZdpTOOvlEeWFduuuUe63pq8Oe8f75Bt7+BhzVvOAdN+fA+OmX9u/mGZkW07pvfci6ImkYh7EQPw5WRoZSAnAlWDTN/ah0dTk7QbQXHIx9/eDmHxXF3zS9oR/wM5MXu1xV8BIeT07LTwbI4Iir1ZEDEmp6FP8YrTderTp7e3bRymVdMM9pkoTp5YnrgIg4JaAQ9DOU7yQc+W8IB/H7PWMHGLN0gzXlEQdtoqLQDfPOtVcC88+T2mwM3ETVD2unih4up+Xn+mzNfFtjF2bJXnFtO/PSNgEJZPXpRoWyVi5rAN/LVS0iFDpUSy5r0UEJ/QBnfsJO3VU+Piss/hbZt4LstLPU86LlABYaxc+pxaJFy6Bc6T/0IKpccGSH1jG2jpV44w9WIeKfkA5c7uiJC2RO8OvSBdxsIq/ZrJkcSAf6EYqp2pQ50POxq0WLAdEDdF2jqxIoNJ8QWv+hZCc1RnWvEemmpFF+9LanLOSL3SDeDJ0Y0RpQs3poJTjK+PjLehixSF8Y6+1GVt+I14olTYsjQ9BUsgUHxaQ1DOlAdZETs8sRkZWUfW+GKLKXM1l5ZqkiUI5j2R7iHvxkekVOudV0Ts1hD5NDKKY6aqdSvp9ijeZTRByV9zVfWHbUwRAInJ2aPwhMlBUfajZzhqlhMLpX7oor27Hqk9wO9kPq5e/i5e/jJhz8rhgUVYxjWpjrBiih6dvPKWuXJav7pB42Wp056ZuLGo9HFgPONhAJXBRRxFVvjFXP2yrutheWJ6QSRzL7PdpPfAF4GZpFn3D7pJOEI4bZtjbpjBcc2ltcZWyb0AlnLlC+fg3lND3Z/X/ukiSKOkwLvP6B7rwS7+Yx1mdc8c6eEMNS4yqtt4ik7u4HiB2TXzzsxVKWPWPgw5EO4FEDIj+VtzMdwzIYQUuelbfpOP3a8cnqimR6Ohb42q+3rGc3YZsvcIjqsr256Dy4Gw1vX+zGYwjzkfevQ1d5s1lsEduVtHe7f3Dq4vHeUt0jCvOmGca/KHwR+wNZOEg5dwYvulc9jC/pmeUJVLidC7kgFECnh6Xwg+Dy7T1hu3VrrHBlBWExVqPw94kXLrQLQimwddRqEXnCF51d0rZQQvhXvwBMMf+4jHOhGapIVF6ER3cFN5/YHRkf+ZBoUpuRGyQv6Nr1zDMGRuodUEQFS+XWVWPHi17UieC8oDGzdZ6+ntIBnvlcJMcP2Z+Plodtl/Wp9ZaH/dgK0vZj5gAWs6v7WvIhh5dmu1BNTufB8zmawL7L6XYQttSu8HE5ObIi3gDcgqLpCMTA4AfWLErrZirC7MjdKb/yCO2sa27S3om/W21iqwoTWRnvmlCnzM+Z1Hw80E6+O3xi+BVs6hY5OZRRnLcymGy9JiIKst/3gDdict3GdY9vP2tzYh2yAuOJ3lc0tsBtC8Y3gNhdU05ur5imx+ARc+9TA2gPF42vYkG9SCfNTPefaqqEIFI3BhuNSf/U99qxser4lFOHIAYaFssMRShvvadu4PtZgSL0zbiYQ3ig9w46d3UmOzTO6rII2RvAcwpUYoaGqDOUxxbpN9CngGIZNF8nw9jBIoZQ44QFLYhPp/Fb7gZa8TAVINZO24yDbkbvCMyWjIewnDG4sqmNI3IwP5kt0JsyDeNhkrKbjDqSNu80bHDTOm7l0O7qrpoLLD8PqpZ5hRQcIIjR+BM7UrjYmXu6cAW8GLnI1A1XMVUWM2Vx7ZWikxsjkl1Z4lAE/viW6jFjI2HB3n0i2NUJcWVHeEQopSrz6TJjfpfvf+Nm4N3cbkdkqVU0EeS0E8HxkTMvSyKrXjD0mWDe9PRD9f2HIj9Pp2++GmFLVITRG+ltkJajw5lRA+6/jhpMKGl9wUlczMB8rXyPtkTkirmXdcAbmxkrIOo9bpB4E9SxUfrI6jbpWgJCieWMui94eN/L+TOQ87xa5z2nTSbCnP7jx9AlBfNLTxCd3Tm+3brx1KF74VPshnXHu9gadnQ11SGLEpx4WQ+A6hzAmALY0Zkv6JDWOwXTHPVSK0HbB3nMRmNNohAdmnHwghB4O4wuKtRumwdn8QxVp0ytqsHwuvGGMsqXVPVyxQivkYV8P21qxfWbDZtlN76np8RIp0QxhxqzjdigQekIVBo/KFaRL6/fobKueDdmuJK6wbAlHQm+6gtRDP0IMtptAgQGoF2SCVHHyygalkq0dFROZqOiKpg+6wmdSS1SosJ+UMZvZIJAYJRkTmwJxOk8dBjzNeEB6FakOu0HvT2UJHxWfqKarPycch5r3noBaoawpzgfmSFgMKw8UUl7dNxtzNbj4yx+YelEMTqgYUZqlouclcJtuHIKHgTaBru+QiGbdewK3iMHIz4x6vMYB4QOPI9l8B0eb5aJ/OPDeb9rVPsTo4FYbvy5ujDc9P4qv/mAqYiVM4MYn0uwDKDZVObEU5FQ91FBl2KDNvWW7m0jl3YFRz6kmobBv41ITrz8RZgWWtXWA57VsOMsBY9YNV91uKFJcwm/whSMSNXw9GpwrnVepRiwyTV1wO8gdDnREsIUI151vUAhUaR+7R0C9sNWswZsq6BduWIvfqp5COqFRb4alf2J70Fa9OaZydoM3EIdmQlOwGWRl37MjBo42XDFk4+mN27PzXq/Oc0jPAkXJZz2EcKvr9mRX/AmNG2jYb1QbaGBp9hkh8Mhn5R2lFbFQrRqtpmkh4ivXLoUd7fzgOmGnRoPRaItsKyp3YGrvwKKx9ROG/G+Gygf0dhu9f2+CceygIFGXyLMTGhbQFbPfinQ46K7aDxhwxFVdeONgpQpjXdzRx9ctff0389GIgqDI2VxjuNBrceHi7rQhZ5seY10vIx9dEYlaE14m9GfqWnjknSC7J3WVlhod3LrK2MC6RDqLWEFnTEVVPSUNVJCV4abAECAwJlTCR2IQpPJEmDGJp7xAX9PPK62b1dIqGaHSRFtlWbfV6jYeSlhrhXC5w4GO2FhPcWRb8+DG1Edn45+HvURnhdnFIvP3AJ06wwQcuXXh0VGh79HNK+apsff6wclsgYAt0YLRH+Vqw0Mrhm2nEchQF0lYvroOGBU+2iL2h2MEohQK8GOtUZOm8GaHcQpRPiwdH7qozYVJnd3lVRkPTWk4XrC6LYRbqo2sKILzzoG+UW1MsMIOzmQgbzopgjwHXhCqqA8dWuEwZGOURV3qwuRPO+JuCDsisBmRoIeyLXgptjQ92hVK9ns8hzsscS7IQ282RU+oqRv/qfhA7sRsEEjwRzpPTPx42R8XOu1oeRtsFbUZ4Ak25OlpkPikOGDbzyNiN0QD+MNhtFEBQr3w4flMgf5NM+wWesc8qoAUMETAl7g5jHw49Q6WWWPCjgIllCRqIT38UKOH3jiBb8yDlf9Gy04yJEQBaEbGWeNwYHqDLIULaFSiqydifNwYep6C8uCwuMFeNaiJyVZWJHRBH1ZIz/XFc5smH17/sA7iQztjy06BiFS+sVSiM6AyYh/QY7ITyh6WbLaLSISv5gk7phviKk0XDPEEWQU1OgMf+DVBv/Chi0lWSaQX5wwfksz/GPxoPY/H4wHhcocDHbHxE9vEnjbPLoIjXD0N0+JBKQGgbiMUCXdKC9uz1a2B+AJs/BVeYqu2Ai7FYI/kiSmyp095P1HPqpwDRmPPLl+kxccPGaHBgQ9PndaerS4T5rKC4Eypi1Ej/We/TXcy86N9TzOQnlFuY0J4YWOdnqepOKLD/+z8w7mDptGU8M1bocMHg4sog/Z7A3vQzHqo12R0hgg6TARzOkqwXQh25AzDjpjde/r5LE/OPD91jwxcRYgeq/uC1HUXXNgJFKbS7TNMtNObE8MB8S0TvmHi7BTOOn36NVFGjpHwcoBXy/CKd6DlB3SdnohSaSUDnmoKI4bmVDoFCNLIjYVPTMI+2ySsnkyiN0C5FdRMgJmMM64Vi3X+gL2dbvPURRVGjzLehc9md6+BDB33md1m828W9jUjI36MuDHoOTKc0OVS8JfGbeGTCUK3mCEg0hgUrLSLIeF0o4Z8iEFMfr7u04akTx9HP1Wl1XJgnM426SHUY46noV4+B4OOQskTJaKk3Sk+H/B04HJkec/g5kBCRcE5pyfb0acfI/+MFO8/AD0ln16430eRa/bCvKeTM3siWYv45U5GOneNc+OeehY9ArHwILCnH8v1jO5puaSlMHgFZ9A8Mc3whCr0U1O0lKuexa7z47oLjY3j0+/9NrJvSLfNGr8ndl2rPBEG1sHCGP0I+nYqcnADPHW3M643NYK/uAAOaq92icxOONAQBp5H82RVk5ZXxXMJZ0o0cjs8nXpKLssoQ+VUUVWsyJ7OCQrBAlf9Hxz//6bOLbtBGAaiy+lXFpUmNHDShhQOxdLqK3lmbP8w9ziG8DB+IUuG2mbNsdpTbk3lAVcxJ1rkhoCffgYHE3931M+wv8IVby3yxdnmBE7ZXZ680BhE6smfJ6Vph4FGBBdDQ2j5usGn5algzKetMpMoly9Z2pcLn1P6CYClSNGwplzl7BxUD1NqxAx0gwvnCks/3CNGIX3GsCzYYv4n9bORSl3yd8tHo4aSYdS0FyyySgvlVWhTVzDECPnVhQ0vbilfOTe4IyRPKem0WJyTu2IJ52BKTxlgoBHBuOkhOoTRLzc2Vy65rXC5Y4ueQE2qKS3hRkVraNEOzdz7RXlQ+ZHDssc0ddqBupN25XDfmClDPDY4OzUTRGMjb/SrmsoEzChZjsrQcbeFjsdtmWGNbvxbPpFUzo0mttNftG7KVvQJQ+ll0VZFQA5a9DHdVgUlsuqBhDv90PbfeK5Rz3JX/lHofWA0gZXYK03m8nxLHx18LW34SFCZOx6Uj22Cr6jg68A3tNZB85WulYJ1Ascm7XtsivRknMu0Y5pPdljs0HtiiJFlNFhJxRyBHfjKFfqc+Bt9zxjjgaUqs44ml4B2cGrFWLFbG37b38rKg6GsbNAOA41Y2dPB2yZ8Qt8UGpIEzahGXVkzqFGDHfRiFCSf2OJWuC8rik2u50K95dN7NuZ4V++94obHLq2vsEddo/uR7CzMnh3e5/ESm7IsFD0cn1uBDmTfOYg+R3yOQvaAnUFlvpy+cNlwgMOGy6thG5tWX9I2hD1j50JHh4c9X1gYvVWMvsrK06MlRrPp8o+VSyx539ZNudCkpdKXpDOIs6c3ogq0F/Vyo7CWC/qTfjZ4dcIp+YUWze4NnNK0w0Aj+j8qqlpn';

    public const FREQUENCY = 'gL6-A$A:#@\'$KT#<c"06"8*"Wb"5-$Kr t%#f+"S4$7;$ v!R=%!6 g@#s}&\'P#rM"a8"K\'!3= #V }o wx*bZ"X="De#2 #~B!dL!\\\"(u qj#Pf L_"e (1K$9--x6 SE"x- .$)/s%rK(w""QV"oQ ;; {_ [| q@(8t-G7#nU MW cS2$9 UJ GH <]*#S !: >!\'kQ \\Z @A)O8 5& E (L""YZ r_&g2 66 ~& *L!n* zO,|Y)B8 V.#X: qI\'V0 B7 s@4<B)>= I-$wt!;A =h ?G+y: p/ |k"}} &w"]?#72 W) j$ @w S !I700V\'gf (Q cf!9{9cL Qa .i #Z, F *M NZ!0c RX!+%((N S$ ]o#hU#{o Z2#j_ 6P d% I!!wX _Z.nH!i.!@y!6T#a$"Yt ?]!kv Jl"]{ sh!2$&x~"5F.8X AF!n2 :A1G@(\'.%5} 24!TZ XY \\P zq G,).J)>} GD _o Xu*di&m0!%Q  L-KA @I ei%4P <S 8m+NI l& Gg&m+"nS$T{%&? YT e( Mu g2 ~., B(\\P N6 .d .M:^6 gg"]s"k2\':6 LU /h#k_ 6-#$3%p9 W$ 7x&)(%_-">[#Q8 VM y( 3q c* vn/va-K> ^N Il LQ/y9 qF *< /k+^O zp K;"bZ!M\\!>(*NW L\' .-#=<"Uz$^:$o? C] F\' /)"+) FC+8H$M&!l1&2\'#n"&K<!d-#"_ jw A{ J(#~e$Yi#(v.Ng#NF!V8 zM"Q4*RH&.X E""n; ^5 41 gM!xj("Q1![ bn #!";?1la yU mR A6)GK @="w5!k$!bX"b[)K-!R) Mq Fl&uz Zx&p\\ <A X\' f&!B_ xj)GW.3F <G *5 .e-}6 (6 C-!U>)S< :r#=;#:C 9Q!xJ,7< w" Tp#*1%|F#Ze&JW Dn /$ ]("4( H{&>!.@h W[ .F"G}/#k :_ ,U ;$04E \\,!/P)h9!Hq f4( f S& 18 M^":\'#w|#]o 7= !# Y]"*4 YZ-<E0$]"B^ QO bn/$G )> zO e>-|+ \\? SY (d$nj b%)p+%aQ 2P oa!U\\ mp$x: S: *$ &.!9( 3-3KD&WW WG"jB\'`),:t I<\'T_ I=($C Pd!;M 5I 3a"h %_G $, .E (_%wZ(WC!*] w> "$ s} WT h;*Kd UY!JW")u",)"v6!AU"Mv RS"hZ "("Ya&e/%5S-w\\"/W#b1 lF*fp%a6$3c$aH#uf!e> cH!]v 58*En+r< Ic v< gh,&\\ +A ;a$2!)I. !8 :w$>E 2Q fH+LA$)$ Gl+NL"y9"43$30 "8 d% 8l u% cV$pm 6C 2O uH .S K@ $= DB B(!f6 =9 <R *I uH A@ dG )A `K NX 3L !sopL %: o1 h0 9/ (U(5?-Q8!&=!iX"f(0\\[ ~V!ii )\\,<? 2W!l*!}#"\\8#NX)pn S0 Iv!{M$Rs#A2#s"!$R k& ]J!Yi {#4jR%m` --$\\D ;(*>X ]K lS"H~\'c; ;K#hP!/^!(l !S#z+##d [H F\\%Z3/b-#)f /O c% 66!YO 2w+{3,}? {N I1  O3^6 !? xV"s5-3I `R wn (E wj Kq\';: KA XU&_\'#b|$<N$2C qX w# iu!yO Fr($1"T@"pg"eA#+3\'g<!w$":w `7$\\ !Z)#qf%I4%$x*pf!kN"/* T9(Z])+?&O5"9"!nP 2< {9 5q R-\'0~5)4 ^= j8 us9#/ @1 }3 Ul0+- U6 *K!&? +K!]p()0 Z$ va!e} Y< fl!rQ *) C( KJ#f4 $;\'v.5aO W` w*!A<0IC yG ru!KR-*/ <\\ ,-!&3!Ap&Jc\'+` V& s5"gB$gF!`>!B0 hO g. ;w"I5 |v7:@&:o %h#zz H"(wx tL rk!q\\*w9 {D Q-!T_!lF!O$%vF&cB 8 "Mp!SZ\'he!HP rq!>N"BA"Y3 wR8^U$.~ ee#K[";,#IZ ;f!KI!eE!XQ d-#s/$JY%Pz%/F$+Y#2$ XX#Ez\'5g$z9"9$!1y vB 51!La @T*\\f0)j ek L3!^@1t- II Zn m@,08 )0!-@!<-! |"Fp&Oj A& N_ U[ P>!-h$D` 3a!K\' `d#uc%';

    protected $_passKey;
    protected $_hash;
    protected $_length;
    protected $_isCommon = false;
    protected $_charsetSize;
    protected $_entropy;
    protected $_strength;

    public function __construct($password, $passKey)
    {
        $this->_passKey = $passKey;
        $this->_hash = core\crypt\Util::passwordHash($password, $passKey);
        $this->_length = mb_strlen((string)$password);
        $this->_isCommon = in_array($password, $this->_getCommonWords());
        $this->_charsetSize = $this->_getCharsetSize($password);
        $this->_entropy = $this->_calculateEntropy($password);
        $this->_strength = $this->_calculateStrength();
    }

    public function getHash()
    {
        return $this->_hash;
    }

    public function getPassKey()
    {
        return $this->_passKey;
    }

    public function getLength()
    {
        return $this->_length;
    }

    public function isCommon()
    {
        return $this->_isCommon;
    }

    public function getCharsetSize()
    {
        return $this->_charsetSize;
    }

    public function getEntropy()
    {
        return $this->_entropy;
    }

    public function getStrength()
    {
        return $this->_strength;
    }





    protected function _getCommonWords()
    {
        return explode("0xFF", (string)gzuncompress((string)base64_decode(self::COMMON)));
    }

    protected function _getFrequencyTable()
    {
        $freqs = [];
        $str = self::FREQUENCY;
        $s = ord(' ');
        $length = strlen($str);

        for ($pos = 0; $pos < $length;) {
            $c = ord($str[$pos++]) - $s;
            $c /= 95;
            $c += ord($str[$pos++]) - $s;
            $c /= 95;
            $c += ord($str[$pos++]) - $s;
            $c /= 95;

            $freqs[] = $c;
        }

        return $freqs;
    }

    protected function _getFrequencyIndex($char)
    {
        $char = strtolower((string)$char);

        if ($char < 'a' || $char > 'z') {
            return 0;
        }

        return ord($char) - ord('a') + 1;
    }

    protected function _getCharsetSize($password)
    {
        $output = 0;
        $password = (string)$password;

        if (preg_match('/[a-z]/', $password)) {
            $output += 26;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $output += 26;
        }

        if (preg_match('/[0-9]/', $password)) {
            $output += 10;
        }

        if (preg_match('/[\!\@\#\$\%\^\&\*\(\)]/', $password)) {
            $output += 10;
        }

        if (preg_match('/[\`\~\-\_\=\+\[\{\]\}\\\|\;\:\'\"\,\<\.\>\/\?]/', $password)) {
            $output += 20;
        }

        if (preg_match('/ /', $password)) {
            $output += 1;
        }

        if (preg_match('/[^ -~]/', $password)) {
            $output += 160;
        }


        return $output;
    }

    protected function _calculateEntropy($password)
    {
        $lower = Dictum::text((string)$password)->toLowerCase();
        $freqTable = $this->_getFrequencyTable();

        $charSet = log($this->_getCharsetSize($password)) / log(2);
        $index1 = $this->_getFrequencyIndex($lower[0]);
        $bits = 0;

        for ($i = 1; $i < $this->_length; $i++) {
            $index2 = $this->_getFrequencyIndex($lower[$i]);
            $c = 1.0 - $freqTable[$index1 * 27 + $index2];
            $bits += $charSet * $c * $c;
            $index1 = $index2;
        }

        return $bits;
    }

    protected function _calculateStrength()
    {
        $multiplier = 1 + (0.5 * ($this->_charsetSize / 93));

        if ($this->_length <= 4) {
            $multiplier *= 0.2;
        } elseif ($this->_length <= 8) {
            $multiplier *= 0.5;
        }

        if ($this->_isCommon) {
            $multiplier *= 0.5;
        }

        if ($this->_entropy >= 128) {
            $output = 100;
        } else {
            $output = $this->_entropy / 128 * 100;
        }

        $output = $output * $multiplier;
        $output = pow($output, 0.5) * 10;

        return min($output, 100);
    }
}
