# coding:utf-8

import urllib
import urllib2
import time
import random


PERIOD = 10
currentTime = 0	# EDIT!!!



for i in range(10000):
	count = 0
	data = '{"timestamps":['
	for j in range(PERIOD):
		rand = random.randint(10, 20)
		for k in range(rand):
			data += str(i * PERIOD + currentTime + j) + ','
			count += 1
		pass
	pass
	data = data[:-1]
	data += '],"loveCount":' + str(count) + '}'
	print data

	req = urllib2.Request('https://live-api.line-apps.com/web/channel/ <<< EDIT >>> /broadcast/ <<< EDIT >>> /love')
	req.add_header('Content-Type', 'application/json')
	req.add_header('X-CastService-WebClient-AccessToken', ' <<< YOUR AccessToken >>> ')

	res = urllib2.urlopen(req, data)
	r = res.read()
	print str(i) + ': ' + r

	time.sleep(PERIOD)

pass