
// License Validate Related Mehods Starts (Do not change)
function xor_encrypt(str, key) {
    let xor = '';
    let tmp: any;
    // tslint:disable-next-line:prefer-for-of
    for (let i = 0; i < str.length; ++i) {
        tmp = str[i];
        for (let j = 0; j < key.length; ++j) {
            // tslint:disable-next-line:no-bitwise
            tmp = String.fromCharCode(tmp.charCodeAt(0) ^ key.charCodeAt(j));
        }
        xor += tmp;
    }
    return xor;
}

export function getrevdata(str) {
    let value = '';
    try {
        value = atob(str);
        value = xor_encrypt(value, '*P&46X-(5u)>#12i06N%');
    } catch (err) {
        console.log(err);
    }
    return value;
}