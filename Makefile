BASE=vmProductAutoParentCategories
PLUGINTYPE=system
VERSION=0.1

PLUGINFILES=$(BASE).php $(BASE).xml index.html
# TRANSDIR=../../../administrator/language/
# TRANSLATIONS=$(call wildcard,$(TRANSDIR)/*/*.plg_$(PLUGINTYPE)_$(BASE).sys.ini)
TRANSLATIONS=$(call wildcard,*.plg_$(PLUGINTYPE)_$(BASE).*ini)
ZIPFILE=plg_$(PLUGINTYPE)_$(BASE)_v$(VERSION).zip

zip: $(PLUGINFILES) $(TRANSLATIONS)
	@echo "Packing all files into distribution file $(ZIPFILE):"
	@zip -r $(ZIPFILE) $(PLUGINFILES) 
	@zip -r --junk-paths $(ZIPFILE) $(TRANSLATIONS)

clean:
	rm -f $(ZIPFILE)
