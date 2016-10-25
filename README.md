# XMIdoc
A tool to generate an API documentation out of XMI files

## Options

| Option        | Type      | Meaning |
| ------------- | --------- | ------- |
| `--xmi`       | *string*  | The input XMI file to parse |
| `--dest`      | *string*  | Destination directory where output will be put |
| `--base-href` | *?string* | An optional `<base href="...">` to set in the HTML headers |

## Example Usage
The following command reads out the *resources/UML.xmi* file and puts all content into *doc*.
See the result for the current UML 2.5 Infrastructure specification here: https://ksm2.github.io/XMIdoc/

```
bin/xmidoc --xmi resources/UML.xmi --dest doc --base-href /XMIdoc/ 
```

## Where to get XMI files
You can get the current UML 2.5 XMI file here: http://www.omg.org/spec/UML/20131001/UML.xmi
