import urllib
import urllib2
import cookielib


# ItemID List - Update(2017/08/13)
# 1 - エメラルド
# 2 - エクレア
# 3 - ウサギの足
# 4 - 羽
# 5 - ネットガン
# 6 - 雲を吸い込む空瓶
# 7 - 3%の確率で壊れる鍵
# 8 - 自主用携帯電話
# 9 - 復活薬
# 10 - 魔法の薬
# 11 - りんご
item_id = 11
item_count = 64
user_name = "Your NAME"
user_pass = "Your Password"


opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookielib.CookieJar()))

data = urllib.urlencode( { 'name' : user_name, 'pass' : user_pass } )
conn = opener.open('https://www.kametan.tokyo/login.php', data)
conn.read()


data2 = urllib.urlencode( { 'INT' : item_count } )
conn = opener.open('https://www.kametan.tokyo/buytask.php?ITEM_ID=' + str(item_id), data2)
conn.read()