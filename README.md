# XMIdoc
A tool to generate an API documentation out of XMI files https://ksm2.github.io/XMIdoc/

## Options

| Option        | Type      | Meaning |
| ------------- | --------- | ------- |
| `--dest`      | *string*  | Destination directory where output will be put |
| `--base-href` | *?string* | An optional `<base href="...">` to set in the HTML headers |
| `--title`     | *?string* | An optional title for the documentation, defaults to “XMI Documentation” |

## Example Usage
The following command reads out the XMI specification file from *http://www.omg.org/spec/UML/20131001/UML.xmi* and puts all content into *doc*.

```
bin/xmidoc --dest doc --base-href /XMIdoc/ http://www.omg.org/spec/UML/20131001/UML.xmi 
```

See the result for the current UML 2.5 Infrastructure specification here: https://ksm2.github.io/XMIdoc/package/UML.html

## Where to get XMI files
You can get current XMI specifications from the OMG for example here: 
* http://www.omg.org/spec/UML/20131001/UML.xmi
* http://www.omg.org/spec/UML/20131001/UMLDI.xmi
* http://www.omg.org/spec/DD/20131001/DC.xmi
* http://www.omg.org/spec/DD/20131001/DG.xmi
* http://www.omg.org/spec/DD/20131001/DI.xmi
* http://www.omg.org/spec/XMI/20131001/XMI-model.xmi

(Updated November 2016)
