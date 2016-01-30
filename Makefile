BASE=vmAutoParentCategories
PLUGINTYPE=system
VERSION=1.3.2

PLUGINFILES=$(BASE).php $(BASE).xml index.html

SYSTRANSLATIONS=$(call wildcard,language/*/*.plg_$(PLUGINTYPE)_$(BASE).*sys.ini)
NONSYSTRANSLATIONS=${SYSTRANSLATIONS:%.sys.ini=%.ini}
TRANSLATIONS=$(SYSTRANSLATIONS) $(NONSYSTRANSLATIONS) $(call wildcard,language/*/index.html) language/index.html
ZIPFILE=plg_$(PLUGINTYPE)_$(BASE)_v$(VERSION).zip

all: zip

$(NONSYSTRANSLATIONS): %.ini: %.sys.ini
	cp $< $@

zip: $(PLUGINFILES) $(TRANSLATIONS) $(SYSTRANSLATIONS) $(NONSYSTRANSLATIONS)
	@echo "Packing all files into distribution file $(ZIPFILE):"
	@zip -r $(ZIPFILE) $(PLUGINFILES)  LICENSE.txt
	@zip -r $(ZIPFILE) $(TRANSLATIONS)

clean:
	rm -f $(ZIPFILE)
