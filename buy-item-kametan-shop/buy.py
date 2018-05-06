# coding:utf-8

import urllib
import urllib2

##### ItemID List - 2017/11/03 UPDATE #####
#  1 - エメラルド
#  2 - エクレア
#  3 - ウサギの足
#  4 - 羽
#  5 - ネットガン
#  6 - 雲を吸い込む空瓶
#  7 - 3%の確率で壊れる鍵
#  8 - 自首用携帯電話
#  9 - 復活薬
# 10 - 魔法の薬
# 11 - りんご
# 12 - 冷凍銃
# 13 - コンパス
# 14 - エリトラ (コメントアウトされてました)
# 15 - かぼちゃ


item_id = 11
item_count = 1
user_cookie = "Cookie(PHPSESSID)"


data = urllib.urlencode({"INT": item_count})
req = urllib2.Request('https://www.kametan.tokyo/buytask.php?ITEM_ID=' + str(item_id))
req.add_header('Content-Type', 'application/x-www-form-urlencoded')
req.add_header('Cookie', 'PHPSESSID=' + user_cookie)
req.add_data(data)

res = urllib2.urlopen(req)
r = res.read()
print r