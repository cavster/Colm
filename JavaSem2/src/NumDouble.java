
public class NumDouble implements Num {
Double d;
public NumDouble(Double d) {
this.d = d;
}
@Override
public void neg() {
this.d = -d;
}
@Override
public void sqrt() {
this.d = Math.sqrt(d);
}
@Override
public String toString() {
return d.toString();
}
}