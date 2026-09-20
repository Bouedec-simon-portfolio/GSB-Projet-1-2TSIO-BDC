"""À exécuter uniquement contre la base éphémère préparée par le workflow GitHub Actions."""
import urllib.request, urllib.parse, urllib.error, http.cookiejar, re, os, datetime
BASE=os.environ.get('GSB_TEST_URL','http://127.0.0.1:8080/')
PWD=os.environ['GSB_TEST_PASSWORD']
class Client:
 def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def req(self,path,data=None):
  request=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
  try:
   with self.opener.open(request) as r:return r.status,r.read().decode(),r.url
  except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
 def post(self,page,path,data):
  status,html,_=self.req(page); assert status==200,(status,html[:200])
  token=re.search(r'name="csrf_test_name" value="([^"]+)"',html);assert token,'CSRF absent'
  return self.req(path,dict(data,csrf_test_name=token.group(1)))
 def login(self,user):return self.post('connexion','connexion',{'login':user,'password':PWD})
a=Client();assert a.req('fiches')[2].endswith('/connexion')
assert a.post('connexion','connexion',{'login':'alice','password':'wrong'})[2].endswith('/connexion')
assert a.login('alice')[2].endswith('/fiches')
month=datetime.datetime.now().strftime('%Y%m');page='fiches/'+month
assert a.post('fiches','fiches',{})[0]==200
assert a.post('fiches','fiches',{})[0]==200  # Créer deux fois ne doit pas dupliquer la fiche.
assert a.post(page,page+'/forfait',{'quantites[ETP]':'0','quantites[KM]':'10','quantites[NUI]':'0','quantites[REP]':'2'})[0]==200
_,html,_=a.post(page,page+'/hors-forfait',{'date':datetime.date.today().isoformat(),'libelle':'TEST TAXI','montant':'12.50'})
assert '68,70' in html and 'TEST TAXI' in html
_,bad,_=a.post(page,page+'/hors-forfait',{'date':datetime.date.today().isoformat(),'libelle':'REFUSER','montant':'-2'})
assert 'REFUSER' not in bad
# Le HTML fourni en libellé doit être affiché comme du texte.
_,html,_=a.post(page,page+'/hors-forfait',{'date':datetime.date.today().isoformat(),'libelle':'<script>alert(1)</script>','montant':'1'})
assert '&lt;script&gt;' in html and '<script>alert(1)</script>' not in html
taxi_row=next(row for row in re.findall(r'<tr>.*?</tr>',html,re.S) if 'TEST TAXI' in row)
line=re.search(r'hors-forfait/(\d+)/supprimer',taxi_row).group(1)
b=Client();b.login('bob');assert b.req(page)[0]==404
b.post('fiches','fiches',{})
b.post(page,page+'/hors-forfait/'+line+'/supprimer',{})
assert 'TEST TAXI' in a.req(page)[1], 'Suppression entre visiteurs'
# Modifier une quantité, puis la remettre à sa valeur attendue pour le contrôle SQL.
_,updated,_=a.post(page,page+'/forfait',{'quantites[ETP]':'0','quantites[KM]':'10','quantites[NUI]':'0','quantites[REP]':'3'})
assert '94,70' in updated
assert a.post(page,page+'/forfait',{'quantites[ETP]':'0','quantites[KM]':'10','quantites[NUI]':'0','quantites[REP]':'2'})[0]==200
_,deleted,_=a.post(page,page+'/hors-forfait/'+line+'/supprimer',{})
assert 'TEST TAXI' not in deleted and '57,20' in deleted
assert a.req('administration')[0]==403
assert a.req(page+'/hors-forfait',{'date':datetime.date.today().isoformat(),'libelle':'SANS TOKEN','montant':'2'})[0] in (200,302,403)
assert 'SANS TOKEN' not in a.req(page)[1]
# La fixture contient la fiche 200001 fermée pour Alice.
assert 'Consultation uniquement' in a.req('fiches/200001')[1]
a.post('fiches/200001','fiches/200001/hors-forfait',{'date':'2000-01-01','libelle':'FERMEE','montant':'1'})
assert 'FERMEE' not in a.req('fiches/200001')[1]
ad=Client();ad.login('admin');assert ad.req('administration')[0]==200
assert a.post(page,'deconnexion',{})[2].endswith('/connexion')
assert a.req(page)[2].endswith('/connexion')
for private_path in ['.env','app/','database/schema.sql','composer.json']:
 assert a.req(private_path)[0] in (403,404), 'Fichier privé exposé : '+private_path
print('Tests HTTP réussis : connexion, frais, total, validation, isolation, CSRF, XSS, fermeture, droits et déconnexion.')
